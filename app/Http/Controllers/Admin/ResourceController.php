<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Http\Controllers\Controller;
use App\Http\Requests\CatalogRequest;
use Illuminate\Http\Request;

abstract class ResourceController extends Controller
{
    protected string $resource;

    public function __construct(protected AdminController $admin) {}

    public function index(Request $request)
    {
        return $this->admin->index($request, $this->resource);
    }

    public function create()
    {
        return $this->admin->form($this->resource);
    }

    public function edit(int $id)
    {
        return $this->admin->form($this->resource, $id);
    }

    public function store(CatalogRequest $request)
    {
        return $this->admin->save($request, $this->resource);
    }

    public function update(CatalogRequest $request, int $id)
    {
        return $this->admin->save($request, $this->resource, $id);
    }

    public function destroy(Request $request, int $id)
    {
        return $this->admin->delete($request, $this->resource, $id);
    }
}

