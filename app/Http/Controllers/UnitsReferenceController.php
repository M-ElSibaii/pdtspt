<?php

namespace App\Http\Controllers;

use App\Models\Dimension;
use App\Models\PhysicalQuantity;
use App\Models\Unit;
use App\Services\UnitsReference;
use Illuminate\Http\Request;

/**
 * Dereferenceable pages for the ISO 23387 reference layer (R-LD-2/3/4).
 * Each entity resolves on OUR platform (pdts.pt) to a human-readable HTML page, or to a
 * machine-readable JSON representation under content negotiation, always citing QUDT as the
 * external authority.
 */
class UnitsReferenceController extends Controller
{
    /** Whether the client asked for JSON (Accept header, ?format=json, or .json suffix). */
    private function wantsJson(Request $request): bool
    {
        return $request->query('format') === 'json'
            || $request->wantsJson()
            || str_ends_with((string) $request->path(), '.json');
    }

    /**
     * Strip a trailing ".json" suffix from a path value (content negotiation).
     * NOTE: must NOT use rtrim($v, '.json') — that strips any trailing '.','j','s','o','n'
     * characters (e.g. "min" -> "mi"), which caused the JSON pages to 404.
     */
    private function stripJson(string $value): string
    {
        return str_ends_with($value, '.json') ? substr($value, 0, -5) : $value;
    }

    public function unit(Request $request, string $code)
    {
        $code = $this->stripJson($code);
        $unit = Unit::where('code', $code)->first();
        abort_if(!$unit, 404, "Unknown unit: {$code}");

        $quantity = $unit->physicalQuantity;
        $dimension = $quantity ? $quantity->dimension : null;
        $authority = UnitsReference::qudtAuthority($unit->reference_uri, $unit->code, 'unit');

        if ($this->wantsJson($request)) {
            return response()->json([
                '@id' => UnitsReference::unitUri($unit->code),
                'type' => 'Unit',
                'guid' => $unit->guid,
                'code' => $unit->code,
                'name' => $unit->name,
                'physicalQuantity' => $quantity ? [
                    'name' => $quantity->name,
                    'languageIsoCode' => $quantity->languageIsoCode,
                    '@id' => UnitsReference::quantityKindUri($quantity->name),
                ] : null,
                'dimension' => $dimension ? [
                    'canonical' => $dimension->canonical,
                    '@id' => UnitsReference::dimensionUri($dimension->canonical),
                ] : null,
                'scale' => $unit->scale,
                'base' => $unit->base,
                'coefficient' => $unit->coefficient,
                'offset' => $unit->offset,
                'sameAs' => $authority['reference'],   // QUDT external authority (R-LD-4)
                'externalAuthority' => $authority,
            ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return view('reference.unit', compact('unit', 'quantity', 'dimension', 'authority'));
    }

    public function quantityKind(Request $request, string $name)
    {
        $name = $this->stripJson($name);
        $quantity = PhysicalQuantity::where('name', $name)->first();
        abort_if(!$quantity, 404, "Unknown physical quantity: {$name}");

        $dimension = $quantity->dimension;
        $units = $quantity->units()->orderBy('code')->get();
        $authority = UnitsReference::qudtAuthority($quantity->reference_uri, $quantity->name, 'quantitykind');

        if ($this->wantsJson($request)) {
            return response()->json([
                '@id' => UnitsReference::quantityKindUri($quantity->name),
                'type' => 'QuantityKind',
                'guid' => $quantity->guid,
                'name' => $quantity->name,
                'languageIsoCode' => $quantity->languageIsoCode,
                'dimension' => $dimension ? [
                    'canonical' => $dimension->canonical,
                    '@id' => UnitsReference::dimensionUri($dimension->canonical),
                ] : null,
                'units' => $units->map(fn ($u) => [
                    'code' => $u->code,
                    '@id' => UnitsReference::unitUri($u->code),
                ])->all(),
                'sameAs' => $authority['reference'],
                'externalAuthority' => $authority,
            ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return view('reference.quantitykind', compact('quantity', 'dimension', 'units', 'authority'));
    }

    public function dimension(Request $request, string $canonical)
    {
        $canonical = $this->stripJson($canonical);
        $dimension = Dimension::where('canonical', $canonical)->first();
        abort_if(!$dimension, 404, "Unknown dimension: {$canonical}");

        $quantities = $dimension->physicalQuantities()->orderBy('name')->get();
        // Exponents in ISO 80000 order.
        $exponents = [
            'Length' => $dimension->exp_length,
            'Mass' => $dimension->exp_mass,
            'Time' => $dimension->exp_time,
            'ElectricCurrent' => $dimension->exp_electric_current,
            'ThermodynamicTemperature' => $dimension->exp_thermodynamic_temperature,
            'AmountOfSubstance' => $dimension->exp_amount_of_substance,
            'LuminousIntensity' => $dimension->exp_luminous_intensity,
        ];

        if ($this->wantsJson($request)) {
            return response()->json([
                '@id' => UnitsReference::dimensionUri($dimension->canonical),
                'type' => 'Dimension',
                'guid' => $dimension->guid,
                'canonical' => $dimension->canonical,
                'exponents' => $exponents,
                'physicalQuantities' => $quantities->map(fn ($q) => [
                    'name' => $q->name,
                    '@id' => UnitsReference::quantityKindUri($q->name),
                ])->all(),
                'sameAs' => $dimension->reference_uri,
            ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return view('reference.dimension', compact('dimension', 'quantities', 'exponents'));
    }
}
