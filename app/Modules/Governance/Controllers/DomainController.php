<?php

namespace App\Modules\Governance\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Governance\Models\Domain;
use App\Modules\Governance\Requests\StoreDomainRequest;
use App\Modules\Governance\Requests\UpdateDomainRequest;
use Illuminate\Http\Request;

class DomainController extends Controller
{
    public function index()
    {
        return response()->json(['data' => Domain::orderBy('name')->get()]);
    }

    public function store(StoreDomainRequest $request)
    {
        $this->authorize('create', Domain::class);

        $domain = Domain::create($request->validated());

        return response()->json(['data' => $domain, 'message' => 'Domain created.'], 201);
    }

    public function show(Domain $domain)
    {
        return response()->json(['data' => $domain->load('policies')]);
    }

    public function update(UpdateDomainRequest $request, Domain $domain)
    {
        $this->authorize('update', $domain);

        $domain->update($request->validated());

        return response()->json(['data' => $domain->fresh(), 'message' => 'Domain updated.']);
    }

    public function destroy(Domain $domain)
    {
        $this->authorize('delete', $domain);

        $domain->delete();

        return response()->json(['message' => 'Domain deleted.']);
    }
}
