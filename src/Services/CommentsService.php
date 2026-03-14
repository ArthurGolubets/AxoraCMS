<?php

namespace HolartWeb\AxoraCMS\Services;

use HolartWeb\AxoraCMS\Models\Callback\TComments;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Collection;

class CommentsService
{
    /**
     * Check if Callback module is installed
     *
     * @throws \Exception
     */
    protected function checkCallbackModule(): void
    {
        if (!Schema::hasTable('t_comments')) {
            throw new \Exception('Callback module is not installed');
        }
    }

    /**
     * Get comments for a product
     *
     * @param int $productId
     * @param bool $moderatedOnly
     * @return Collection
     * @throws \Exception
     */
    public function getProductComments(int $productId, bool $moderatedOnly = true): Collection
    {
        $this->checkCallbackModule();

        $query = TComments::where('product_id', $productId)
            ->orderBy('created_at', 'desc');

        if ($moderatedOnly) {
            $query->where('is_moderated', true);
        }

        return $query->get();
    }

    /**
     * Get paginated comments for a product
     *
     * @param int $productId
     * @param bool $moderatedOnly
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     * @throws \Exception
     */
    public function getProductCommentsPaginated(int $productId, bool $moderatedOnly = true, int $perPage = 10)
    {
        $this->checkCallbackModule();

        $query = TComments::where('product_id', $productId)
            ->orderBy('created_at', 'desc');

        if ($moderatedOnly) {
            $query->where('is_moderated', true);
        }

        return $query->paginate($perPage);
    }

    /**
     * Create a new comment
     *
     * @param array $data
     * @return TComments
     * @throws \Exception
     */
    public function createComment(array $data): TComments
    {
        $this->checkCallbackModule();

        return TComments::create([
            'name' => $data['name'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'comment' => $data['comment'],
            'rating' => $data['rating'] ?? null,
            'product_id' => $data['product_id'],
            'is_moderated' => $data['is_moderated'] ?? false,
        ]);
    }

    /**
     * Update comment
     *
     * @param int $commentId
     * @param array $data
     * @return TComments
     * @throws \Exception
     */
    public function updateComment(int $commentId, array $data): TComments
    {
        $this->checkCallbackModule();

        $comment = TComments::findOrFail($commentId);
        $comment->update($data);

        return $comment;
    }

    /**
     * Delete comment
     *
     * @param int $commentId
     * @return bool
     * @throws \Exception
     */
    public function deleteComment(int $commentId): bool
    {
        $this->checkCallbackModule();

        $comment = TComments::findOrFail($commentId);
        return $comment->delete();
    }

    /**
     * Moderate comment (approve/reject)
     *
     * @param int $commentId
     * @param bool $approve
     * @return TComments
     * @throws \Exception
     */
    public function moderateComment(int $commentId, bool $approve = true): TComments
    {
        $this->checkCallbackModule();

        $comment = TComments::findOrFail($commentId);
        $comment->update(['is_moderated' => $approve]);

        return $comment;
    }

    /**
     * Get average rating for a product
     *
     * @param int $productId
     * @return float|null
     * @throws \Exception
     */
    public function getProductAverageRating(int $productId): ?float
    {
        $this->checkCallbackModule();

        $avgRating = TComments::where('product_id', $productId)
            ->where('is_moderated', true)
            ->whereNotNull('rating')
            ->avg('rating');

        return $avgRating ? round($avgRating, 1) : null;
    }

    /**
     * Get rating statistics for a product
     *
     * @param int $productId
     * @return array
     * @throws \Exception
     */
    public function getProductRatingStats(int $productId): array
    {
        $this->checkCallbackModule();

        $comments = TComments::where('product_id', $productId)
            ->where('is_moderated', true)
            ->whereNotNull('rating')
            ->get();

        if ($comments->isEmpty()) {
            return [
                'average' => null,
                'count' => 0,
                'distribution' => [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0],
            ];
        }

        $distribution = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
        foreach ($comments as $comment) {
            if ($comment->rating >= 1 && $comment->rating <= 5) {
                $distribution[$comment->rating]++;
            }
        }

        return [
            'average' => round($comments->avg('rating'), 1),
            'count' => $comments->count(),
            'distribution' => $distribution,
        ];
    }

    /**
     * Get recent comments
     *
     * @param int $limit
     * @param bool $moderatedOnly
     * @return Collection
     * @throws \Exception
     */
    public function getRecentComments(int $limit = 10, bool $moderatedOnly = true): Collection
    {
        $this->checkCallbackModule();

        $query = TComments::orderBy('created_at', 'desc')
            ->limit($limit);

        if ($moderatedOnly) {
            $query->where('is_moderated', true);
        }

        return $query->get();
    }

    /**
     * Get comments count for a product
     *
     * @param int $productId
     * @param bool $moderatedOnly
     * @return int
     * @throws \Exception
     */
    public function getProductCommentsCount(int $productId, bool $moderatedOnly = true): int
    {
        $this->checkCallbackModule();

        $query = TComments::where('product_id', $productId);

        if ($moderatedOnly) {
            $query->where('is_moderated', true);
        }

        return $query->count();
    }

    /**
     * Check if module is available (without throwing exception)
     *
     * @return bool
     */
    public function isModuleAvailable(): bool
    {
        return Schema::hasTable('t_comments');
    }
}
