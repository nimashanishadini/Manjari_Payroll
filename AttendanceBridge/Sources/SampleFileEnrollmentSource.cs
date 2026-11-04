using System.Text.Json;

internal sealed class SampleFileEnrollmentSource(string path) : IEnrollmentSource
{
    public async Task<Enrollment> ReadAsync(CancellationToken cancellationToken)
    {
        var content = await File.ReadAllTextAsync(path, cancellationToken);
        return JsonSerializer.Deserialize<Enrollment>(content)
            ?? throw new ArgumentException("Empty enrollment JSON.");
    }
}
