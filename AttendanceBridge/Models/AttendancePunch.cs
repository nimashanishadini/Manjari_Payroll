using System.Text.Json.Serialization;

internal sealed record AttendancePunch(
    [property: JsonPropertyName("employee_id")] string? EmployeeId,
    [property: JsonPropertyName("punch_time")] string? PunchTime,
    [property: JsonPropertyName("verify_type")] string? VerifyType,
    [property: JsonPropertyName("mask_status")] string? MaskStatus);
