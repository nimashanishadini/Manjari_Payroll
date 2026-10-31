
@props([
    'title' => 'Partner IT ERP',
    'section' => null
])

@php
    $currentSection = $section ?? (
        request()->routeIs('dashboard') ? 'dashboard' :
        (
            request()->routeIs(
    'hr.organization-structure.*',
    'hr.employee-management.*',
    'hr.person.*'
) ? 'person' :
            (
                request()->routeIs('hr.device.*') ? 'device' :
                (
                    request()->routeIs(
                        'hr.shifts-attendance.*',
                        'hr.leaves.*'
                    ) ? 'attendance' : 'payroll'
                )
            )
        )
    );
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <meta
        name="csrf-token"
        content="{{ csrf_token() }}"
    >

    <title>{{ $title }} | Partner IT ERP</title>

    @vite([
        'resources/css/app.css',
        'resources/js/app.js'
    ])

    <style>
        /* ==========================================
           PARTNER IT ERP - THEME VARIABLES
        ========================================== */

        :root {
            --erp-red: #f10f32;
            --erp-red-dark: #b80e2a;

            --erp-dark: #20252d;
            --erp-dark-deep: #10151d;

            --erp-sidebar: #0b1018;
            --erp-sidebar-hover: #19212d;

            --erp-border: #303844;

            --erp-text: #f4f6fa;
            --erp-muted: #9ca6b6;

            --erp-header-height: 60px;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            min-height: 100%;
        }

        body {
            background: #ffffff;
            color: #363636;
            font-family:
                Inter,
                Arial,
                Helvetica,
                sans-serif;
        }

        /* ==========================================
           MAIN HEADER
        ========================================== */

        .erp-header {
            width: 100%;
            height: var(--erp-header-height);

            display: flex;
            align-items: stretch;

            background: linear-gradient(
                110deg,
                #10151d 0%,
                #20252d 65%,
                #111720 100%
            );

            color: var(--erp-text);

            border-bottom: 1px solid var(--erp-border);

            box-shadow:
                0 2px 12px rgba(0, 0, 0, 0.15);

            white-space: nowrap;

            position: sticky;
            top: 0;
            z-index: 50;
        }

        /* ==========================================
           COMPANY LOGO / BRAND
        ========================================== */

        .erp-brand {
            width: 255px;
            min-width: 255px;

            display: flex;
            align-items: center;
            gap: 12px;

            padding: 7px 16px;

            background: #0b1018;

            color: #ffffff;
            text-decoration: none;

            border-right: 1px solid #273040;

            transition: background 0.2s ease;
        }

        .erp-brand:hover {
            background: #121a26;
        }

        .erp-brand-logo {
            width: 100%;
            height: 46px;

            display: flex;
            align-items: center;
            justify-content: center;

            flex-shrink: 0;

            background: #ffffff;
            border-radius: 6px;

            overflow: hidden;
        }

        .erp-brand-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        /* ==========================================
           TOP NAVIGATION
        ========================================== */

        .erp-navigation {
            display: flex;
            align-items: stretch;

            flex: 1;
            min-width: 0;

            overflow-x: auto;
            overflow-y: hidden;

            scrollbar-width: none;
        }

        .erp-navigation::-webkit-scrollbar {
            display: none;
        }

        .erp-navigation a {
            position: relative;

            display: flex;
            align-items: center;
            justify-content: center;
            gap: 9px;

            padding: 0 24px;

            color: #c4cad4;
            text-decoration: none;

            font-size: 14px;
            font-weight: 500;

            border-right: 1px solid #303743;

            transition:
                background 0.2s ease,
                color 0.2s ease;

            white-space: nowrap;
        }

        .erp-navigation a:hover {
            background: #303744;
            color: #ffffff;
        }

        .erp-navigation a.active {
            background: linear-gradient(
                135deg,
                #f10f32 0%,
                #b80e2a 100%
            );

            color: #ffffff;
            font-weight: 650;

            box-shadow:
                inset 0 -3px 0 #ff5367;
        }

        .erp-navigation a.active:hover {
            background: linear-gradient(
                135deg,
                #fa2446,
                #c51231
            );
        }

        /* ==========================================
           NAVIGATION ICONS
        ========================================== */

        .erp-nav-icon {
            width: 18px;
            height: 18px;

            display: inline-flex;
            align-items: center;
            justify-content: center;

            flex-shrink: 0;
        }

        .erp-nav-icon svg {
            width: 18px;
            height: 18px;

            stroke: currentColor;
            stroke-width: 1.8;

            fill: none;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        /* ==========================================
           USER INFORMATION
        ========================================== */

        .erp-user {
            display: flex;
            align-items: center;
            gap: 10px;

            padding: 0 20px;

            color: #e4e8ef;

            font-size: 13px;

            border-left: 1px solid #303743;

            flex-shrink: 0;
        }

        .erp-user-avatar {
            width: 32px;
            height: 32px;

            display: flex;
            align-items: center;
            justify-content: center;

            background: linear-gradient(
                135deg,
                #f10f32,
                #a30d27
            );

            border-radius: 50%;

            color: white;
            font-size: 13px;
            font-weight: 700;
        }

        .erp-user-name {
            max-width: 150px;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* ==========================================
           APPLICATION FRAME
        ========================================== */

        .erp-frame {
            display: flex;

            width: 100%;
            min-height: calc(
                100vh - var(--erp-header-height)
            );
        }

        .erp-main {
            flex: 1;
            min-width: 0;

            background: #ffffff;
        }

        .erp-placeholder {
            min-height: calc(
                100vh - var(--erp-header-height)
            );

            background: #ffffff;
        }

        /* ==========================================
           DASHBOARD
        ========================================== */

        .erp-dashboard {
            padding: 28px;
            background: #f5f6f8;
            min-height: calc(
                100vh - var(--erp-header-height)
            );
        }

        .erp-dashboard-grid {
            display: grid;

            grid-template-columns:
                repeat(4, minmax(0, 1fr));

            gap: 16px;
            margin-top: 20px;
        }

        .erp-stat {
            padding: 22px;

            background: #ffffff;

            border: 1px solid #e5e7eb;
            border-radius: 10px;

            box-shadow:
                0 2px 8px rgba(15, 23, 42, 0.04);
        }

        .erp-stat small {
            color: #64748b;
            font-size: 12px;
            font-weight: 600;
        }

        .erp-stat strong {
            display: block;

            margin-top: 10px;

            font-size: 30px;
            font-weight: 750;

            color: #172033;
        }

        /* ==========================================
           RESPONSIVE - TABLET
        ========================================== */

        @media (max-width: 1100px) {

            .erp-brand {
                width: 215px;
                min-width: 215px;
            }

            .erp-navigation a {
                padding: 0 17px;
                font-size: 13px;
            }

            .erp-user {
                padding: 0 12px;
            }

            .erp-user-name {
                display: none;
            }

            .erp-dashboard-grid {
                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }
        }

        /* ==========================================
           RESPONSIVE - MOBILE
        ========================================== */

        @media (max-width: 700px) {

            :root {
                --erp-header-height: 56px;
            }

            .erp-header {
                height: 56px;
            }

            .erp-brand {
                width: 145px;
                min-width: 145px;

                padding: 6px 10px;
                justify-content: center;
            }

            .erp-brand-logo {
                width: 100%;
                height: 40px;
            }

            .erp-navigation a {
                padding: 0 13px;
                font-size: 12px;
            }

            .erp-nav-icon {
                width: 16px;
                height: 16px;
            }

            .erp-user {
                display: none;
            }

            .erp-frame {
                flex-direction: column;
            }

            .erp-dashboard {
                padding: 16px;
            }

            .erp-dashboard-grid {
                grid-template-columns: 1fr;
            }
            /* Prevent page-wide horizontal overflow */
html,
body {
    width: 100%;
    max-width: 100%;
    margin: 0;
    overflow-x: clip;
}

.erp-header {
    width: 100%;
    max-width: 100%;
    min-width: 0;
}

.erp-frame {
    display: flex;
    width: 100%;
    max-width: 100%;
    min-width: 0;
    overflow: clip;
}

.erp-main {
    display: block;
    flex: 1 1 0%;
    width: 0;
    min-width: 0;
    max-width: 100%;
    overflow-x: hidden;
}

/* Keep the existing main sidebar at a fixed width */
.erp-frame > .erp-side {
    flex-shrink: 0;
}

@media (max-width: 600px) {
    .erp-frame {
        display: block;
        overflow: visible;
    }

    .erp-main {
        width: 100%;
        min-width: 0;
    }
}
        }

        /* Reserve footer space; only the content pane scrolls. */
        .erp-app-body {
            height: 100dvh;
            min-height: 0;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        .erp-app-body > .erp-header { flex-shrink: 0; }
        .erp-app-body .erp-frame {
            flex: 1 1 0%;
            min-height: 0;
            min-width: 0;
            max-width: 100%;
            overflow: hidden;
        }
        .erp-app-body .erp-main {
            display: flex;
            flex-direction: column;
            flex: 1 1 0%;
            width: 0;
            min-width: 0;
            min-height: 0;
            max-width: 100%;
            overflow: hidden;
        }
        .erp-page-content {
            flex: 1 1 0%;
            min-height: 0;
            min-width: 0;
            overflow: auto;
        }
        .erp-page-footer {
            flex: none;
            min-width: 0;
            padding: 10px 16px;
            border-top: 1px solid #e2e8f0;
            background: #fff;
        }
        .erp-page-footer > footer { margin-top: 0; }
        .erp-app-body .erp-frame > .erp-side {
            height: 100%;
            min-height: 0;
            position: relative;
            top: 0;
            overflow: hidden;
        }
        @media (max-width: 700px) {
            .erp-app-body .erp-frame { display: flex; flex-direction: column; }
            .erp-app-body .erp-main { width: 100%; }
            .erp-app-body .erp-frame > .erp-side {
                width: 100%;
                min-width: 100%;
                height: auto;
                max-height: 35dvh;
                flex-shrink: 0;
            }
            .erp-page-footer { padding: 6px 10px; }
            .erp-page-footer > footer { padding: 8px 12px; gap: 6px; }
        }
    </style>
</head>

<body @auth class="erp-app-body" @endauth>

@auth

    <!-- ======================================
         TOP HEADER
    ======================================= -->

    <header class="erp-header">

        <!-- COMPANY LOGO -->

        <a
            href="{{ route('dashboard') }}"
            class="erp-brand"
        >

            <div class="erp-brand-logo">

                <img
                    src="{{ asset('images/partner-it-logo.png') }}"
                    alt="Partner IT ERP Logo"
                >

            </div>

        </a>

        <!-- ======================================
             TOP NAVIGATION
        ======================================= -->

        <nav
            class="erp-navigation"
            aria-label="Main navigation"
        >

            @if(auth()->user()->role === 'employee')
                <a href="{{ route('self-service.index') }}" class="active">Self-Service</a>
            @else
            <!-- DASHBOARD -->

            <a
                href="{{ route('dashboard') }}"
                class="{{ $currentSection === 'dashboard' ? 'active' : '' }}"
                title="Dashboard"
                aria-label="Dashboard"
                @if($currentSection === 'dashboard')
                    aria-current="page"
                @endif
            >

                <span class="erp-nav-icon">

                    <svg viewBox="0 0 24 24">

                        <path
                            d="M3 10.5L12 3l9 7.5"
                        />

                        <path
                            d="M5 9.5V21h14V9.5"
                        />

                        <path
                            d="M9 21v-7h6v7"
                        />

                    </svg>

                </span>

            </a>

            <!-- PERSON -->

            <a
                href="{{ route('hr.organization-structure.index') }}"
                class="{{ $currentSection === 'person' ? 'active' : '' }}"
                @if($currentSection === 'person')
                    aria-current="page"
                @endif
            >

                <span class="erp-nav-icon">

                    <svg viewBox="0 0 24 24">

                        <circle
                            cx="12"
                            cy="8"
                            r="4"
                        />

                        <path
                            d="M4 21v-2a8 8 0 0 1 16 0v2"
                        />

                    </svg>

                </span>

                Person

            </a>

            <!-- ATTENDANCE -->

            <a
                href="{{ route('hr.shifts-attendance.index') }}"
                class="{{ $currentSection === 'attendance' ? 'active' : '' }}"
                @if($currentSection === 'attendance')
                    aria-current="page"
                @endif
            >

                <span class="erp-nav-icon">

                    <svg viewBox="0 0 24 24">

                        <rect
                            x="3"
                            y="5"
                            width="18"
                            height="16"
                            rx="2"
                        />

                        <path
                            d="M7 3v4M17 3v4M3 10h18"
                        />

                        <path
                            d="M8 15l3 3 5-5"
                        />

                    </svg>

                </span>

                Attendance

            </a>

            <!-- DEVICE -->

            <a
                href="{{ route('hr.device.index') }}"
                class="{{ $currentSection === 'device' ? 'active' : '' }}"
                @if($currentSection === 'device')
                    aria-current="page"
                @endif
            >

                <span class="erp-nav-icon">

                    <svg viewBox="0 0 24 24">

                        <rect
                            x="5"
                            y="2"
                            width="14"
                            height="20"
                            rx="2"
                        />

                        <path
                            d="M9 6h6M10 18h4"
                        />

                    </svg>

                </span>

                Device

            </a>

            <!-- PAYROLL -->

            <a
                href="{{ route('hr.payroll.index') }}"
                class="{{ $currentSection === 'payroll' ? 'active' : '' }}"
                @if($currentSection === 'payroll')
                    aria-current="page"
                @endif
            >

                <span class="erp-nav-icon">

                    <svg viewBox="0 0 24 24">

                        <rect
                            x="2"
                            y="5"
                            width="20"
                            height="15"
                            rx="2"
                        />

                        <path
                            d="M2 10h20"
                        />

                        <circle
                            cx="16"
                            cy="15"
                            r="1"
                        />

                    </svg>

                </span>

                Payroll

            </a>

            @endif
        </nav>

        <!-- ======================================
             LOGGED IN USER
        ======================================= -->

        <x-alarm-notifications />

        <div class="erp-user">

            <div class="erp-user-avatar">

                {{ strtoupper(
                    substr(
                        auth()->user()->name ?? 'A',
                        0,
                        1
                    )
                ) }}

            </div>

            <span class="erp-user-name">

                {{ auth()->user()->name ?? 'Admin' }}

            </span>

        </div>

    </header>

    <!-- ======================================
         APPLICATION CONTENT
    ======================================= -->

    <div class="erp-frame">

        <!--
            Dashboard = No sidebar

            Person / Attendance / Device / Payroll
            = Show corresponding sidebar
        -->

        @if($currentSection !== 'dashboard' || auth()->user()->role === 'employee')

            <x-sidebar :section="$currentSection" />

        @endif

        <main class="erp-main">

            <div class="erp-page-content">
                {{ $slot }}
            </div>

            <div class="erp-page-footer">
                <x-page-footer />
            </div>

        </main>

    </div>

@else

    <main>

        {{ $slot }}

    </main>

@endauth

</body>
</html>
