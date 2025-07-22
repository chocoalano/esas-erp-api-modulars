<?php

namespace App\GeneralModule\Controllers;

use App\GeneralModule\Requests\Notification\NotificationIndexRequest;
use App\GeneralModule\Services\NotificationService;
use Illuminate\Routing\Controller as BaseController;

class NotificationController extends BaseController
{
    protected NotificationService $service;

    public function __construct(NotificationService $service)
    {
        $this->service = $service;
    }

    public function index(NotificationIndexRequest $request)
    {
        $validated = $request->validated();

        return response()->json($this->service->paginate(
            $validated['page'],
            $validated['limit'],
            $validated['search'] ?? [],
            $validated['sortBy'] ?? []
        ));
    }

    public function show($id)
    {
        return response()->json($this->service->find($id));
    }

    public function destroy($id)
    {
        return response()->json($this->service->delete($id));
    }
}
