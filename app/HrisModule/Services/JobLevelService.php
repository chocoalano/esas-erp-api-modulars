<?php

namespace App\HrisModule\Services;

use App\HrisModule\Repositories\Contracts\JobLevelRepositoryInterface;
use Illuminate\Database\Eloquent\Model; // Import Model for better return type hinting
use Illuminate\Pagination\LengthAwarePaginator; // Import for paginate return type

class JobLevelService
{
    /**
     * Create a new JobLevelService instance.
     *
     * @param JobLevelRepositoryInterface $repo The job level repository instance.
     */
    public function __construct(protected JobLevelRepositoryInterface $repo)
    {
        // No additional logic needed here, constructor injection handles it.
    }

    /**
     * Paginate job levels.
     *
     * @param int $page The current page number.
     * @param int $limit The number of items per page.
     * @param array $search An array of search parameters.
     * @param array $sortBy An array of sorting parameters.
     * @return LengthAwarePaginator The paginated collection of job levels.
     */
    public function paginate(int $page, int $limit, array $search, array $sortBy): LengthAwarePaginator
    {
        return $this->repo->paginate($page, $limit, $search, $sortBy);
    }

    /**
     * Paginate trashed job levels.
     *
     * @param int $page The current page number.
     * @param int $limit The number of items per page.
     * @param array|null $filter An optional array of filter parameters.
     * @return LengthAwarePaginator The paginated collection of trashed job levels.
     */
    public function paginateTrashed(int $page, int $limit, array $search, array $sortBy): LengthAwarePaginator
    {
        return $this->repo->paginateTrashed($page, $limit, $search, $sortBy);
    }

    /**
     * Retrieve data for a job level form, optionally filtered by company ID.
     *
     * @param Request $request The incoming HTTP request.
     * @return array|null The form data, or null if not found. (Adjust return type based on repository's actual return)
     */
    public function form(?int $companyId): ?array
    {
        return $this->repo->form($companyId);
    }

    /**
     * Create a new job level record.
     *
     * @param array $data The data for the new job level.
     * @return Model The created job level model instance.
     */
    public function create(array $data): Model
    {
        return $this->repo->create($data);
    }

    /**
     * Update an existing job level record.
     *
     * @param int|string $id The ID of the job level to update.
     * @param array $data The data to update the job level with.
     * @return Model The updated job level model instance.
     */
    public function update(int|string $id, array $data): Model
    {
        return $this->repo->update($id, $data);
    }

    /**
     * Soft delete a job level record.
     *
     * @param int|string $id The ID of the job level to delete.
     * @return bool True if the job level was deleted, false otherwise.
     */
    public function delete(int|string $id): bool
    {
        return $this->repo->delete($id);
    }

    /**
     * Force delete a job level record permanently.
     *
     * @param int|string $id The ID of the job level to force delete.
     * @return bool True if the job level was force deleted, false otherwise.
     */
    public function forceDelete(int|string $id): bool
    {
        return $this->repo->forceDelete($id);
    }

    /**
     * Restore a soft-deleted job level record.
     *
     * @param int|string $id The ID of the job level to restore.
     * @return Model The restored job level model instance.
     */
    public function restore(int|string $id): Model
    {
        return $this->repo->restore($id);
    }

    /**
     * Export job level data.
     *
     * @param string|null $name Filter by name.
     * @param string|null $createdAt Filter by created date.
     * @param string|null $updatedAt Filter by updated date.
     * @param string|null $startRange Filter by start date range.
     * @param string|null $endRange Filter by end date range.
     * @return mixed The export data. (Consider more specific return type like StreamedResponse or a custom Export class)
     */
    public function export(
        ?string $name = null,
        ?string $createdAt = null,
        ?string $updatedAt = null,
        ?string $startRange = null,
        ?string $endRange = null,
    ): mixed { // Changed to nullable strings for parameters with default null
        return $this->repo->export(
            $name,
            $createdAt,
            $updatedAt,
            $startRange,
            $endRange,
        );
    }

    /**
     * Import job level data from a file.
     *
     * @param mixed $file The file containing the import data. (Consider more specific type like UploadedFile)
     * @return mixed The import result.
     */
    public function import(mixed $file): mixed
    {
        return $this->repo->import($file);
    }

    /**
     * Find a job level by its ID.
     *
     * @param int|string $id The ID of the job level to find.
     * @return Model|null The job level model instance, or null if not found.
     */
    public function find(int|string $id): ?Model
    {
        return $this->repo->find($id);
    }
}
