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

    /**
     * API collection: all units, each with its identity URI, physical quantity, dimension and
     * QUDT authority link. Eager-loads the quantity+dimension chain to avoid N+1.
     */
    public function units()
    {
        $units = Unit::with(['physicalQuantity.dimension', 'dimension'])->orderBy('code')->get();

        return response()->json($units->map(function ($unit) {
            $quantity = $unit->physicalQuantity;
            $dimension = $unit->effectiveDimension();
            $authority = UnitsReference::qudtAuthority($unit->reference_uri, $unit->code, 'unit');

            return [
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
                'sameAs' => $authority['reference'],
            ];
        })->all(), 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * API collection: all physical quantities (quantity kinds), each with its identity URI and
     * dimension link. Eager-loads the dimension to avoid N+1.
     */
    public function quantityKinds()
    {
        $quantities = PhysicalQuantity::with('dimension')->orderBy('name')->get();

        return response()->json($quantities->map(function ($quantity) {
            $dimension = $quantity->dimension;
            $authority = UnitsReference::qudtAuthority($quantity->reference_uri, $quantity->name, 'quantitykind');

            return [
                '@id' => UnitsReference::quantityKindUri($quantity->name),
                'type' => 'QuantityKind',
                'guid' => $quantity->guid,
                'name' => $quantity->name,
                'namePt' => $quantity->namePt,
                'languageIsoCode' => $quantity->languageIsoCode,
                'dimension' => $dimension ? [
                    'canonical' => $dimension->canonical,
                    '@id' => UnitsReference::dimensionUri($dimension->canonical),
                ] : null,
                'sameAs' => $authority['reference'],
            ];
        })->all(), 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * API collection: all dimensions, each with its identity URI and the 7 SI exponents
     * (ISO 80000 order).
     */
    public function dimensions()
    {
        $dimensions = Dimension::orderBy('canonical')->get();

        return response()->json($dimensions->map(function ($dimension) {
            return [
                '@id' => UnitsReference::dimensionUri($dimension->canonical),
                'type' => 'Dimension',
                'guid' => $dimension->guid,
                'canonical' => $dimension->canonical,
                'exponents' => [
                    'Length' => $dimension->exp_length,
                    'Mass' => $dimension->exp_mass,
                    'Time' => $dimension->exp_time,
                    'ElectricCurrent' => $dimension->exp_electric_current,
                    'ThermodynamicTemperature' => $dimension->exp_thermodynamic_temperature,
                    'AmountOfSubstance' => $dimension->exp_amount_of_substance,
                    'LuminousIntensity' => $dimension->exp_luminous_intensity,
                ],
                'sameAs' => $dimension->reference_uri,
            ];
        })->all(), 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Browsable index of the reference layer: every physical quantity with its units, and
     * every dimension. ?q= filters by unit symbol or name, or quantity name (EN/PT).
     */
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        // Alphabetical in the language the page is shown in.
        $quantities = PhysicalQuantity::with(['dimension', 'units' => fn ($u) => $u->orderBy('code')])->get()
            ->sortBy(fn ($pq) => strtolower(\Illuminate\Support\Str::ascii(\App\Support\Lang::pick($pq->namePt ?: $pq->name, $pq->name))))
            ->values();
        $unclassified = Unit::whereNull('physical_quantity_guid')->with('dimension')->orderBy('code')->get();
        $dimensions = Dimension::withCount('physicalQuantities')->orderBy('canonical')->get();

        if ($q !== '') {
            $needle = mb_strtolower($q);
            $hit = fn (?string $s) => $s !== null && str_contains(mb_strtolower($s), $needle);
            $resolved = UnitsReference::resolveCode($q);
            $unitHit = fn ($u) => $hit($u->code) || $hit($u->name) || $u->code === $resolved;
            $quantities = $quantities->filter(function ($pq) use ($hit, $unitHit) {
                if ($hit($pq->name) || $hit($pq->namePt)) {
                    return true;
                }
                $pq->setRelation('units', $pq->units->filter($unitHit)->values());
                return $pq->units->isNotEmpty();
            })->values();
            $unclassified = $unclassified->filter($unitHit)->values();
            $dimensions = $dimensions->filter(fn ($d) => $hit($d->canonical))->values();
        }

        return view('reference.index', compact('q', 'quantities', 'unclassified', 'dimensions'));
    }

    public function unit(Request $request, string $code)
    {
        $code = $this->stripJson($code);
        // Case-sensitive: "MW" (megawatt) and "mW" (milliwatt) are different units,
        // and MySQL's default collation would confuse them.
        $unit = Unit::whereRaw('BINARY `code` = ?', [$code])->first();
        abort_if(!$unit, 404, "Unknown unit: {$code}");

        $quantity = $unit->physicalQuantity;
        $dimension = $unit->effectiveDimension();
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
                    'namePt' => $quantity->namePt,
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

        $siblings = $quantity ? $quantity->units()->where('guid', '<>', $unit->guid)->orderBy('code')->get() : collect();
        $properties = UnitsReference::propertiesUsing([$unit->code]);

        return view('reference.unit', compact('unit', 'quantity', 'dimension', 'authority', 'siblings', 'properties'));
    }

    public function quantityKind(Request $request, string $name)
    {
        $name = $this->stripJson($name);
        $quantity = PhysicalQuantity::whereRaw('BINARY `name` = ?', [$name])->first();
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
                'namePt' => $quantity->namePt,
                'languageIsoCode' => $quantity->languageIsoCode,
                'dimension' => $dimension ? [
                    'canonical' => $dimension->canonical,
                    '@id' => UnitsReference::dimensionUri($dimension->canonical),
                ] : null,
                'units' => $units->map(fn ($u) => [
                    'code' => $u->code,
                    'name' => $u->name,
                    '@id' => UnitsReference::unitUri($u->code),
                ])->all(),
                'sameAs' => $authority['reference'],
                'externalAuthority' => $authority,
            ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $properties = UnitsReference::propertiesUsing($units->pluck('code')->all());

        return view('reference.quantitykind', compact('quantity', 'dimension', 'units', 'authority', 'properties'));
    }

    public function dimension(Request $request, string $canonical)
    {
        $canonical = $this->stripJson($canonical);
        $dimension = Dimension::whereRaw('BINARY `canonical` = ?', [$canonical])->first();
        abort_if(!$dimension, 404, "Unknown dimension: {$canonical}");

        $quantities = $dimension->physicalQuantities()->orderBy('name')->get();
        $units = $dimension->units()->with('physicalQuantity')->orderBy('code')->get();
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
                'units' => $units->map(fn ($u) => [
                    'code' => $u->code,
                    '@id' => UnitsReference::unitUri($u->code),
                ])->all(),
                'sameAs' => $dimension->reference_uri,
            ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return view('reference.dimension', compact('dimension', 'quantities', 'units', 'exponents'));
    }
}
