<?php

declare(strict_types=1);

namespace App\Modules\ForgeEvents\Controllers\Hub;

use App\Modules\ForgeEvents\Services\QueueHubService;
use App\Modules\ForgeSqlOrm\ORM\Paginator;
use App\Modules\ForgeWire\Attributes\Action;
use App\Modules\ForgeWire\Attributes\Reactive;
use App\Modules\ForgeWire\Attributes\State;
use App\Modules\ForgeWire\Traits\ReactiveControllerHelper;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\Http\Attributes\Middleware;
use Forge\Core\Http\Response;
use Forge\Core\Routing\Route;
use Forge\Traits\ControllerHelper;

#[Service]
#[Middleware('web')]
#[Middleware('auth')]
#[Middleware('hub-permissions')]
#[Reactive]
final class QueueController
{
  use ControllerHelper;
  use ReactiveControllerHelper;

  #[State]
  public array $jobs = [];

  #[State]
  public string $statusFilter = '';

  #[State]
  public string $queueFilter = '';

  #[State]
  public string $search = '';

  #[State]
  public string $sortColumn = 'created_at';

  #[State]
  public string $sortDirection = 'desc';

  #[State]
  public int $currentPage = 1;

  #[State]
  public int $perPage = 10;

  #[State]
  public array $selectedJobs = [];

  #[State(shared: true)]
  public array $stats = [];

  public ?Paginator $paginator = null;

  public function __construct(
    private readonly QueueHubService $queueService
  ) {
  }

  #[Route("/hub/queues")]
  public function index(): Response
  {
    $this->loadJobs();
    $this->loadStats();

    $queues = $this->queueService->getQueues();

    return $this->view("pages/hub/queues", [
      'jobs' => $this->jobs,
      'stats' => $this->stats,
      'paginator' => $this->paginator,
      'queues' => $queues,
      'selectedJobs' => $this->selectedJobs,
      'sortColumn' => $this->sortColumn,
      'sortDirection' => $this->sortDirection,
      'search' => $this->search,
      'statusFilter' => $this->statusFilter,
      'queueFilter' => $this->queueFilter,
    ]);
  }

  #[Action]
  public function retryJob(int $jobId): void
  {
    if ($this->queueService->retryJob($jobId)) {
      $this->flash('success', 'Job queued for retry');
    } else {
      $this->flash('error', 'Failed to retry job');
    }
    $this->loadJobs();
    $this->loadStats();
  }

  #[Action]
  public function deleteJob(int $jobId): void
  {
    if ($this->queueService->deleteJob($jobId)) {
      $this->flash('success', 'Job deleted successfully');
      $this->selectedJobs = array_filter($this->selectedJobs, fn($id) => $id !== $jobId);
    } else {
      $this->flash('error', 'Failed to delete job');
    }
    $this->loadJobs();
    $this->loadStats();
  }

  #[Action]
  public function triggerJob(int $jobId): void
  {
    if ($this->queueService->triggerJob($jobId)) {
      $this->flash('success', 'Job triggered successfully');
    } else {
      $this->flash('error', 'Failed to trigger job');
    }
    $this->loadJobs();
    $this->loadStats();
  }

  #[Action]
  public function bulkRetry(): void
  {
    if (empty($this->selectedJobs)) {
      $this->flash('warning', 'No jobs selected');
      return;
    }

    $successCount = 0;
    foreach ($this->selectedJobs as $jobId) {
      if ($this->queueService->retryJob($jobId)) {
        $successCount++;
      }
    }

    if ($successCount > 0) {
      $this->flash('success', "{$successCount} job(s) queued for retry");
    } else {
      $this->flash('error', 'Failed to retry selected jobs');
    }

    $this->selectedJobs = [];
    $this->loadJobs();
    $this->loadStats();
  }

  #[Action]
  public function bulkDelete(): void
  {
    if (empty($this->selectedJobs)) {
      $this->flash('warning', 'No jobs selected');
      return;
    }

    $successCount = 0;
    foreach ($this->selectedJobs as $jobId) {
      if ($this->queueService->deleteJob($jobId)) {
        $successCount++;
      }
    }

    if ($successCount > 0) {
      $this->flash('success', "{$successCount} job(s) deleted successfully");
    } else {
      $this->flash('error', 'Failed to delete selected jobs');
    }

    $this->selectedJobs = [];
    $this->loadJobs();
    $this->loadStats();
  }

  #[Action]
  public function refresh(): void
  {
    $this->loadJobs();
    $this->loadStats();
  }

  #[Action]
  public function clearFilters(): void
  {
    $this->statusFilter = '';
    $this->queueFilter = '';
    $this->search = '';
    $this->currentPage = 1;
    $this->loadJobs();
  }

  #[Action]
  public function applyFilters(): void
  {
    $this->currentPage = 1;
    $this->loadJobs();
  }

  #[Action]
  public function input(...$keys): void
  {
    if (in_array('statusFilter', $keys) || in_array('queueFilter', $keys) || in_array('search', $keys)) {
      $this->currentPage = 1;
      $this->loadJobs();
    }
  }

  #[Action]
  public function sort(string $column): void
  {
    if ($this->sortColumn === $column) {
      $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
    } else {
      $this->sortColumn = $column;
      $this->sortDirection = 'asc';
    }
    $this->loadJobs();
  }

  #[Action]
  public function changePage(int $page): void
  {
    $this->currentPage = max(1, $page);
    $this->loadJobs();
  }

  #[Action]
  public function toggleJobSelection(int $jobId): void
  {
    $index = array_search($jobId, $this->selectedJobs);
    if ($index !== false) {
      unset($this->selectedJobs[$index]);
      $this->selectedJobs = array_values($this->selectedJobs);
    } else {
      $this->selectedJobs[] = $jobId;
    }
  }

  #[Action]
  public function selectAll(): void
  {
    $this->selectedJobs = array_column($this->jobs, 'id');
  }

  #[Action]
  public function deselectAll(): void
  {
    $this->selectedJobs = [];
  }

  private function loadJobs(): void
  {
    $filters = [
      'status' => $this->statusFilter,
      'queue' => $this->queueFilter,
      'search' => $this->search,
    ];

    $this->paginator = $this->queueService->getJobs(
      $filters,
      $this->sortColumn,
      $this->sortDirection,
      $this->currentPage,
      $this->perPage
    );

    $this->jobs = $this->paginator->items();

    // Pre-load all job details for client-side expansion
    foreach ($this->jobs as &$job) {
      $jobDetails = $this->queueService->getJobDetails($job['id']);
      if ($jobDetails) {
        $job['details'] = $jobDetails['details'] ?? null;
      }
    }
  }

  private function loadStats(): void
  {
    $this->stats = $this->queueService->getStats();
  }
}
