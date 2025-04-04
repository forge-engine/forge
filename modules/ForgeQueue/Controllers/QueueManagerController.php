<?php

namespace Forge\Modules\ForgeQueue\Controllers;

use Forge\Core\Helpers\Redirect;
use Forge\Http\Request;
use Forge\Http\Response;
use Forge\Modules\ForgeQueue\Queue;
use Forge\Modules\ForgeQueue\Repositories\JobRepository;
use Forge\Modules\ForgeQueue\Enums\JobStatus;
use Forge\Modules\ForgeAuth\AuthService;

class QueueManagerController
{
    private Queue $queue;
    private JobRepository $repository;
    private AuthService $auth;

    public function __construct(Queue $queue, JobRepository $repository, AuthService $auth)
    {
        $this->queue = $queue;
        $this->repository = $repository;
        $this->auth = $auth;
    }

    public function index(Request $request): Response
    {
        $this->checkAuthorization();

        $page = $request->getQuery('page', 1);
        $status = $request->getQuery('status', 'pending');
        $queue = $request->getQuery('queue', 'default');

        return (new Response())->html(
            view('queue-manager/index', [
                'jobs' => $this->repository->paginate($page, 20, $status, $queue),
                'statuses' => JobStatus::cases(),
                'queues' => $this->repository->getQueues(),
                'currentStatus' => $status,
                'currentQueue' => $queue
            ])
        );
    }

    public function show(Request $request, string $id): Response
    {
        $this->checkAuthorization();

        $job = $this->repository->find($id);

        return (new Response())->html(
            view('queue-manager/show', [
                'job' => $job,
                'payload' => json_decode($job->payload, true)
            ])
        );
    }

    public function retry(Request $request, string $id): Response
    {
        $this->checkAuthorization();

        $this->repository->retryJob($id);
        return $this->redirectBackWithSuccess('Job queued for retry');
    }

    public function delete(Request $request, string $id): Response
    {
        $this->checkAuthorization();

        $this->repository->delete($id);
        return $this->redirectBackWithSuccess('Job deleted');
    }

    public function retryAllFailed(Request $request): Response
    {
        $this->checkAuthorization();

        $this->repository->retryAllFailed();
        return $this->redirectBackWithSuccess('All failed jobs queued for retry');
    }

    private function checkAuthorization(): void
    {
        if (!$this->auth->user()?->isAdmin()) {
            abort(403, 'Unauthorized');
        }
    }

    private function redirectBackWithSuccess(string $message): Response
    {
        $this->auth->session()->setFlash('success', $message);
        return Redirect::to($_SERVER['HTTP_REFERER'] ?? '/queue-manager');
    }
}
