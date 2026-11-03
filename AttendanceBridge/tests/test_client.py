"""Standard-library integration checks against a local mock API; no real tokens/data."""
import json
import os
from pathlib import Path
import subprocess
import tempfile
import threading
import unittest
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer

ROOT = Path(__file__).resolve().parents[1]
DOTNET = os.environ.get('DOTNET', 'dotnet')
DLL = ROOT / 'bin/Debug/net8.0/AttendanceBridge.dll'


class ClientTests(unittest.TestCase):
    def setUp(self):
        self.temp = tempfile.TemporaryDirectory()
        self.addCleanup(self.temp.cleanup)
        self.sample = Path(self.temp.name) / 'sample.json'
        result = self.run_client('--create-sample', str(self.sample))
        self.assertEqual(0, result.returncode, result.stderr)

    def run_client(self, *args, env=None):
        return subprocess.run([DOTNET, str(DLL), *args], text=True, capture_output=True,
                              env={**os.environ, **(env or {})}, cwd=self.temp.name, timeout=30)

    def test_create_dry_run_and_no_overwrite(self):
        before = self.sample.read_bytes()
        self.assertEqual(0, self.run_client('--dry-run', str(self.sample)).returncode)
        self.assertEqual(1, self.run_client('--create-sample', str(self.sample)).returncode)
        self.assertEqual(before, self.sample.read_bytes())

    def test_invalid_id_and_timestamp_rejected(self):
        original = json.loads(self.sample.read_text())
        for key, value in [('raw_enrollment_id', 25), ('fetched_at', '2026-01-01 08:00:00')]:
            payload = dict(original)
            payload[key] = value
            self.sample.write_text(json.dumps(payload))
            self.assertEqual(1, self.run_client('--dry-run', str(self.sample)).returncode)

    def mock_send(self, statuses, command="--send", sample=None):
        requests = []

        class Handler(BaseHTTPRequestHandler):
            def do_POST(self):
                requests.append((self.path, self.rfile.read(int(self.headers['Content-Length']))))
                code = statuses[min(len(requests) - 1, len(statuses) - 1)]
                self.send_response(code)
                self.send_header('Content-Type', 'application/json')
                self.end_headers()
                self.wfile.write(json.dumps({'data': {'id': 7, 'status': 'Pending HR Review', 'created': code == 201}}).encode())

            def log_message(self, *args):
                pass

        server = ThreadingHTTPServer(('127.0.0.1', 0), Handler)
        thread = threading.Thread(target=server.serve_forever, daemon=True)
        thread.start()
        try:
            result = self.run_client(command, str(sample or self.sample), env={
                'BRIDGE_API_URL': f'http://127.0.0.1:{server.server_port}',
                'BRIDGE_DEVICE_ID': '1', 'BRIDGE_TOKEN': 'a' * 64})
        finally:
            server.shutdown()
            server.server_close()
            thread.join()
        return result, requests

    def test_success(self):
        result, requests = self.mock_send([201])
        self.assertEqual(0, result.returncode, result.stderr)
        self.assertEqual('/api/biometric/devices/1/enrollments', requests[0][0])
        self.assertEqual(self.sample.read_bytes().decode().strip(), requests[0][1].decode().strip())

    def test_temporary_failure_retries_same_payload(self):
        result, requests = self.mock_send([503, 200])
        self.assertEqual(0, result.returncode, result.stderr)
        self.assertEqual(2, len(requests))
        self.assertEqual(requests[0], requests[1])

    def test_rejection_not_retried(self):
        result, requests = self.mock_send([401])
        self.assertEqual(1, result.returncode)
        self.assertEqual(1, len(requests))
        self.assertTrue(self.sample.exists())
        self.assertNotIn('a' * 64, result.stdout + result.stderr)

    def test_sample_punch_delivery_and_employee_pending_retains_file(self):
        punch = Path(self.temp.name) / 'punch.json'
        self.assertEqual(0, self.run_client('--create-punch-sample', str(punch)).returncode)
        payload = json.loads(punch.read_text())
        self.assertEqual('TEST_BRIDGE_001', payload['employee_id'])
        self.assertEqual(0, self.run_client('--dry-run-punch', str(punch)).returncode)
        result, requests = self.mock_send([201], '--send-punch', punch)
        self.assertEqual(0, result.returncode, result.stderr)
        self.assertEqual('/api/biometric/devices/1/punches', requests[0][0])
        self.assertEqual(payload, json.loads(requests[0][1]))
        result, requests = self.mock_send([409], '--send-punch', punch)
        self.assertEqual(2, result.returncode)
        self.assertEqual(1, len(requests))
        self.assertTrue(punch.exists())
        self.assertIn('resend after HR promotion', result.stderr)

    def hikvision_sync(self, pages, extra_args=()):
        device_requests, api_requests, auth_headers = [], [], []

        class Handler(BaseHTTPRequestHandler):
            def do_POST(self):
                payload = json.loads(self.rfile.read(int(self.headers['Content-Length'])))
                if self.path.startswith('/ISAPI/'):
                    auth_headers.append(self.headers.get('Authorization', ''))
                    if not self.headers.get('Authorization', '').startswith('Digest '):
                        self.send_response(401)
                        self.send_header('WWW-Authenticate', 'Digest realm="device", nonce="testnonce", algorithm=MD5, qop="auth"')
                        self.end_headers()
                        return
                    device_requests.append(payload)
                    code, data = pages[min(len(device_requests)-1, len(pages)-1)]
                else:
                    api_requests.append((self.headers.get('Authorization'), payload))
                    code, data = 201, {'data': {'id': len(api_requests), 'status': 'Pending HR Review', 'created': True}}
                self.send_response(code)
                self.send_header('Content-Type', 'application/json')
                self.end_headers()
                self.wfile.write(json.dumps(data).encode())

            def log_message(self, *args):
                pass

        server = ThreadingHTTPServer(('127.0.0.1', 0), Handler)
        thread = threading.Thread(target=server.serve_forever, daemon=True)
        thread.start()
        try:
            origin = f'http://127.0.0.1:{server.server_port}'
            result = self.run_client('--sync-hikvision', *extra_args, env={
                'BRIDGE_API_URL': origin, 'BRIDGE_DEVICE_ID': '1', 'BRIDGE_TOKEN': 'a' * 64,
                'HIKVISION_URL': origin, 'HIKVISION_USERNAME': 'test-device', 'HIKVISION_PASSWORD': 'test-secret'})
        finally:
            server.shutdown()
            server.server_close()
            thread.join()
        return result, device_requests, api_requests, auth_headers

    def test_hikvision_digest_pagination_and_api_mapping(self):
        result, requests, sent, auth = self.hikvision_sync([
            (200, {'UserInfoSearch': {'responseStatusStrg': 'MORE', 'totalMatches': 2,
                                    'UserInfo': [{'employeeNo': '00025', 'name': 'Kamal'}]}}),
            (200, {'UserInfoSearch': {'responseStatusStrg': 'OK', 'totalMatches': 2,
                                    'UserInfo': [{'employeeNo': 26, 'name': None}]}}),
        ])
        self.assertEqual(0, result.returncode, result.stderr)
        self.assertEqual([0, 1], [r['UserInfoSearchCond']['searchResultPosition'] for r in requests])
        self.assertEqual(1, len({r['UserInfoSearchCond']['searchID'] for r in requests}))
        self.assertEqual(['00025', '26'], [r[1]['raw_enrollment_id'] for r in sent])
        self.assertTrue(all(r[0] == 'Bearer ' + 'a' * 64 for r in sent))
        self.assertTrue(any(h.startswith('Digest ') for h in auth))
        self.assertFalse(any('Bearer' in h for h in auth))
        self.assertNotIn('test-secret', result.stdout + result.stderr)

    def test_hikvision_single_user_filter_and_missing_user_do_not_import_others(self):
        pages = [(200, {'UserInfoSearch': {'responseStatusStrg': 'OK', 'totalMatches': 2,
                                         'UserInfo': [{'employeeNo': '00025'}, {'employeeNo': '00026'}]}})]
        result, _, sent, _ = self.hikvision_sync(pages, ('--employee-id', '00026'))
        self.assertEqual(0, result.returncode, result.stderr)
        self.assertEqual(['00026'], [r[1]['raw_enrollment_id'] for r in sent])
        result, _, sent, _ = self.hikvision_sync(pages, ('--employee-id', 'MISSING'))
        self.assertEqual(1, result.returncode)
        self.assertEqual([], sent)

    def test_hikvision_partial_fetch_is_not_imported(self):
        result, _, sent, _ = self.hikvision_sync([
            (200, {'UserInfoSearch': {'responseStatusStrg': 'MORE', 'totalMatches': 2,
                                    'UserInfo': [{'employeeNo': '00025'}]}}),
            (500, {}),
        ])
        self.assertEqual(1, result.returncode)
        self.assertEqual([], sent)

    def test_hikvision_repeated_page_is_rejected(self):
        page = (200, {'UserInfoSearch': {'responseStatusStrg': 'MORE', 'totalMatches': 2,
                                       'UserInfo': [{'employeeNo': '00025'}]}})
        result, _, sent, _ = self.hikvision_sync([page, page])
        self.assertEqual(1, result.returncode)
        self.assertEqual([], sent)

    def test_hikvision_empty_device(self):
        result, _, sent, _ = self.hikvision_sync([
            (200, {'UserInfoSearch': {'responseStatusStrg': 'NO MATCH', 'totalMatches': 0}}),
        ])
        self.assertEqual(0, result.returncode, result.stderr)
        self.assertEqual([], sent)


if __name__ == '__main__':
    unittest.main()
