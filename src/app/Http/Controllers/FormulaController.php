<?php

namespace App\Http\Controllers;

use App\Models\UserFormula;
use App\Services\Backtest\Strategies\CustomFormulaStrategy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use NXP\MathExecutor;

class FormulaController extends Controller
{
    public function index(): JsonResponse
    {
        $formulas = UserFormula::where('user_id', Auth::id())
            ->orderByDesc('updated_at')
            ->get(['id', 'name', 'description', 'formula', 'top_n']);

        return response()->json(['data' => $formulas]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'formula' => 'required|string|max:2000',
            'top_n' => 'nullable|integer|min:1|max:100',
        ]);

        // Validate formula syntax
        try {
            $executor = new MathExecutor();
            $executor->setVars(array_fill_keys(array_keys(CustomFormulaStrategy::VARIABLES), 1.0));
            $executor->execute($data['formula']);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Formulas sintakses kļūda: ' . $e->getMessage()], 422);
        }

        $formula = UserFormula::updateOrCreate(
            ['user_id' => Auth::id(), 'name' => $data['name']],
            [
                'description' => $data['description'] ?? null,
                'formula' => $data['formula'],
                'top_n' => $data['top_n'] ?? 20,
            ]
        );

        return response()->json(['data' => $formula]);
    }

    public function destroy(UserFormula $formula): JsonResponse
    {
        if ($formula->user_id !== Auth::id()) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $formula->delete();
        return response()->json(['success' => true]);
    }

    /**
     * Validate formula syntax + return list of unknown variables.
     * No DB hit — just AST validation.
     */
    public function validateFormula(Request $request): JsonResponse
    {
        $request->validate([
            'formula' => 'required|string|max:2000',
        ]);

        $formula = $request->input('formula');

        try {
            $executor = new MathExecutor();
            $executor->setVars(array_fill_keys(array_keys(CustomFormulaStrategy::VARIABLES), 1.0));
            $result = $executor->execute($formula);

            return response()->json([
                'valid' => true,
                'sample_result' => $result,    // with all vars = 1.0
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'valid' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
