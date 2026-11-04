// Future adapters supply metadata through this boundary; delivery remains shared.
internal interface IEnrollmentSource
{
    Task<Enrollment> ReadAsync(CancellationToken cancellationToken);
}
