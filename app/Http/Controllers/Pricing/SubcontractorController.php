<?php

namespace App\Http\Controllers\Pricing;

use App\Http\Controllers\Controller;
use App\Models\Subcontractor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubcontractorController extends Controller
{
    public function index(Request $request): View
    {
        $search = (string) $request->query('search', '');

        $subcontractors = Subcontractor::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('pricing.subcontractors.index', [
            'subcontractors' => $subcontractors,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function create(): View
    {
        return view('pricing.subcontractors.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
        ]);

        Subcontractor::create($data + ['is_active' => true]);

        return redirect()
            ->route('pricing.subcontractors.index')
            ->with('status', __('Subcontractor created.'));
    }

    public function edit(Subcontractor $subcontractor): View
    {
        return view('pricing.subcontractors.edit', [
            'subcontractor' => $subcontractor,
        ]);
    }

    public function update(Request $request, Subcontractor $subcontractor): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
        ]);

        $subcontractor->update($data);

        return redirect()
            ->route('pricing.subcontractors.index')
            ->with('status', __('Subcontractor updated.'));
    }

    public function toggle(Subcontractor $subcontractor): RedirectResponse
    {
        $subcontractor->update(['is_active' => ! $subcontractor->is_active]);

        return redirect()
            ->route('pricing.subcontractors.index')
            ->with('status', __('Subcontractor status updated.'));
    }
}
