using System.Text.Json.Serialization;

internal sealed record Enrollment(
    [property: JsonPropertyName("raw_enrollment_id")] string? Id,
    [property: JsonPropertyName("raw_name")] string? Name,
    [property: JsonPropertyName("fetched_at")] string? FetchedAt);
