<?php

namespace Forge\Modules\ForgeOrm\Pagination;

use Forge\Modules\ForgeOrm\Collection;


class Paginator
{
    /**
     * @var Collection
     */
    protected Collection $items;

    /**
     * @var int
     */
    protected int $total;

    /**
     * @var int
     */
    protected int $perPage;

    /**
     * @var int
     */
    protected int $currentPage;

    /**
     * @var array<string, mixed>
     */
    protected array $options;

    /**
     * Constructor for Paginator.
     *
     * @param Collection $items
     * @param int $total
     * @param int $perPage
     * @param int $currentPage
     * @param array<string, mixed> $options
     */
    public function __construct(Collection $items, int $total, int $perPage, int $currentPage, array $options = [])
    {
        $this->items = $items;
        $this->total = $total;
        $this->perPage = $perPage;
        $this->currentPage = $currentPage;
        $this->options = $options;
    }

    /**
     * Get the items for the current page.
     *
     * @return Collection
     */
    public function items(): Collection
    {
        return $this->items;
    }

    /**
     * Get the total number of items (before pagination).
     *
     * @return int
     */
    public function total(): int
    {
        return $this->total;
    }

    /**
     * Get the number of items per page.
     *
     * @return int
     */
    public function perPage(): int
    {
        return $this->perPage;
    }

    /**
     * Get the current page number.
     *
     * @return int
     */
    public function currentPage(): int
    {
        return $this->currentPage;
    }

    /**
     * Check if there are more pages than one.
     *
     * @return bool
     */
    public function hasPages(): bool
    {
        return $this->total() > $this->perPage();
    }

    /**
     * Get the URL for the next page (placeholder - needs request context to be fully functional).
     *
     * @return string|null
     */
    public function nextPageUrl(): ?string
    {
        if ($this->currentPage() < $this->lastPage()) {
            return '?' . http_build_query(['page' => $this->currentPage() + 1]); // Basic query string for example
        }
        return null;
    }

    /**
     * Get the URL for the previous page (placeholder - needs request context to be fully functional).
     *
     * @return string|null
     */
    public function previousPageUrl(): ?string
    {
        if ($this->currentPage() > 1) {
            return '?' . http_build_query(['page' => $this->currentPage() - 1]); // Basic query string for example
        }
        return null;
    }

    /**
     * Get the last page number.
     *
     * @return int
     */
    public function lastPage(): int
    {
        return max(1, (int)ceil($this->total() / $this->perPage()));
    }

    /**
     * Resolve the current page number from the request (using $_GET['page'] for now).
     *
     * @return int
     */
    public static function resolveCurrentPage(): int
    {
        if (isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0) {
            return (int)$_GET['page'];
        }
        return 1; // Default to page 1
    }

    /**
     * Get pagination links HTML (basic placeholder - needs more robust implementation).
     *
     * @return string
     */
    public function links(): string
    {
        if (!$this->hasPages()) {
            return '';
        }

        $output = '<nav role="navigation" aria-label="Pagination Navigation"><ul class="pagination">';
        $output .= $this->getPreviousButton();
        $output .= $this->getNumbers();
        $output .= $this->getNextButton();
        $output .= '</ul></nav>';

        return $output;
    }

    /**
     * Get the "previous" pagination element.
     *
     * @return string
     */
    protected function getPreviousButton(): string
    {
        if ($this->currentPage() <= 1) {
            return '<li class="page-item disabled" aria-disabled="true"><span class="page-link">Previous</span></li>';
        }

        $url = $this->previousPageUrl();
        return '<li class="page-item"><a class="page-link" href="' . htmlspecialchars($url) . '" rel="prev">Previous</a></li>';
    }

    /**
     * Get the page number pagination elements.
     *
     * @return string
     */
    protected function getNumbers(): string
    {
        $output = '';
        for ($page = 1; $page <= $this->lastPage(); $page++) {
            if ($page === $this->currentPage()) {
                $output .= '<li class="page-item active" aria-current="page"><span class="page-link">' . $page . '</span></li>';
            } else {
                $url = '?' . http_build_query(['page' => $page]);
                $output .= '<li class="page-item"><a class="page-link" href="' . htmlspecialchars($url) . '">' . $page . '</a></li>';
            }
        }
        return $output;
    }


    /**
     * Get the "next" pagination element.
     *
     * @return string
     */
    protected function getNextButton(): string
    {
        if ($this->currentPage() >= $this->lastPage()) {
            return '<li class="page-item disabled" aria-disabled="true"><span class="page-link">Next</span></li>';
        }

        $url = $this->nextPageUrl();
        return '<li class="page-item"><a class="page-link" href="' . htmlspecialchars($url) . '" rel="next">Next</a></li>';
    }
}