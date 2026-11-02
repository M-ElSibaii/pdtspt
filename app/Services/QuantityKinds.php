<?php

namespace App\Services;

/**
 * Classifies a unit into the physical quantity (ISO 23387 QuantityKind) it measures:
 * mm → length, N/mm² → pressure, W/(m·K) → thermal conductivity.
 *
 * The SI dimension decides by default ({@see BY_DIMENSION}); units whose dimension is shared
 * by several quantities (torque vs energy, angle vs ratio) or that have no physical dimension
 * (currencies, data sizes, environmental indicators) are classified explicitly. A unit that
 * fits nothing here stays unclassified rather than being given a made-up quantity.
 *
 * Names are English (they are the ISO 23386 "physical quantity | language" value, en.EN);
 * each carries its Portuguese label for the site.
 */
class QuantityKinds
{
    /** Quantity name (en) => Portuguese label. Every name used below must be listed here. */
    public const NAMES = [
        'without'                                => 'sem grandeza',
        'dimensionless ratio'                    => 'razão adimensional',
        'efficiency ratio'                       => 'rácio de eficiência',
        'plane angle'                            => 'ângulo plano',
        'solid angle'                            => 'ângulo sólido',
        'mass fraction'                          => 'fração mássica',
        'strain'                                 => 'extensão',
        'relative humidity'                      => 'humidade relativa',
        'humidity ratio'                         => 'teor de humidade',
        'length'                                 => 'comprimento',
        'area per length'                        => 'área por comprimento',
        'area'                                   => 'área',
        'volume per length'                      => 'volume por comprimento',
        'volume'                                 => 'volume',
        'second moment of area'                  => 'momento de inércia de área',
        'mass'                                   => 'massa',
        'time'                                   => 'tempo',
        'frequency'                              => 'frequência',
        'rotational speed'                       => 'velocidade de rotação',
        'angular velocity'                       => 'velocidade angular',
        'angular acceleration'                   => 'aceleração angular',
        'activity'                               => 'atividade (radioatividade)',
        'air change rate'                        => 'taxa de renovação de ar',
        'electric current'                       => 'corrente elétrica',
        'magnetomotive force'                    => 'força magnetomotriz',
        'thermodynamic temperature'              => 'temperatura termodinâmica',
        'amount of substance'                    => 'quantidade de substância',
        'luminous intensity'                     => 'intensidade luminosa',
        'luminous flux'                          => 'fluxo luminoso',
        'speed'                                  => 'velocidade',
        'air permeability'                       => 'permeabilidade ao ar',
        'acceleration'                           => 'aceleração',
        'kinematic viscosity'                    => 'viscosidade cinemática',
        'dynamic viscosity'                      => 'viscosidade dinâmica',
        'volume flow rate'                       => 'caudal volúmico',
        'mass flow rate'                         => 'caudal mássico',
        'density'                                => 'massa volúmica',
        'mass concentration'                     => 'concentração mássica',
        'area density'                           => 'massa por unidade de área',
        'linear density'                         => 'massa por unidade de comprimento',
        'force'                                  => 'força',
        'force per length'                       => 'força por unidade de comprimento',
        'pressure'                               => 'pressão',
        'specific fan power'                     => 'potência específica do ventilador',
        'stress intensity factor'                => 'fator de intensidade de tensão',
        'energy'                                 => 'energia',
        'torque'                                 => 'momento (binário)',
        'rotational stiffness'                   => 'rigidez rotacional',
        'bending stiffness'                      => 'rigidez à flexão',
        'power'                                  => 'potência',
        'power per mass'                         => 'potência por unidade de massa',
        'power per length'                       => 'potência por unidade de comprimento',
        'energy per area'                        => 'energia por unidade de área',
        'heat flux density'                      => 'densidade de fluxo de calor',
        'irradiance'                             => 'irradiância',
        'specific energy'                        => 'energia específica',
        'heat capacity'                          => 'capacidade térmica',
        'specific heat capacity'                 => 'capacidade térmica mássica',
        'thermal conductivity'                   => 'condutibilidade térmica',
        'thermal transmittance'                  => 'coeficiente de transmissão térmica',
        'thermal resistance'                     => 'resistência térmica',
        'thermal conductance'                    => 'condutância térmica',
        'coefficient of thermal expansion'       => 'coeficiente de dilatação térmica',
        'temperature change rate'                => 'taxa de variação de temperatura',
        'degree days'                            => 'graus-dia',
        'electric potential difference'          => 'tensão elétrica',
        'electrical resistance'                  => 'resistência elétrica',
        'electrical resistance per length'       => 'resistência elétrica por comprimento',
        'electrical conductance'                 => 'condutância elétrica',
        'electrical conductivity'                => 'condutividade elétrica',
        'electrical resistivity'                 => 'resistividade elétrica',
        'electric charge'                        => 'carga elétrica',
        'capacitance'                            => 'capacidade elétrica',
        'inductance'                             => 'indutância',
        'magnetic flux'                          => 'fluxo magnético',
        'magnetic flux density'                  => 'densidade de fluxo magnético',
        'magnetic field strength'                => 'intensidade de campo magnético',
        'current density'                        => 'densidade de corrente',
        'electric field strength'                => 'intensidade de campo elétrico',
        'electric dipole moment'                 => 'momento dipolar elétrico',
        'illuminance'                            => 'iluminância',
        'luminance'                              => 'luminância',
        'luminous efficacy'                      => 'eficácia luminosa',
        'amount-of-substance concentration'      => 'concentração molar',
        'specific weight'                        => 'peso volúmico',
        'reciprocal length'                      => 'inverso do comprimento',
        'reciprocal area'                        => 'inverso da área',
        'moment of inertia'                      => 'momento de inércia',
        'angular momentum'                       => 'momento angular',
        'momentum'                               => 'quantidade de movimento',
        'compressibility'                        => 'compressibilidade',
        'airflow resistivity'                    => 'resistividade ao escoamento do ar',
        'airflow resistance'                     => 'resistência ao escoamento do ar',
        'water vapour permeability'              => 'permeabilidade ao vapor de água',
        'currency'                               => 'moeda',
        'information'                            => 'quantidade de informação',
        'data rate'                              => 'débito de dados',
        'sound level'                            => 'nível sonoro',
        'signal level'                           => 'nível de sinal',
        'turbidity'                              => 'turvação',
        'water hardness'                         => 'dureza da água',
        'salinity'                               => 'salinidade',
        'slip resistance'                        => 'resistência ao escorregamento',
        'hardness'                               => 'dureza',
        'image resolution'                       => 'resolução de imagem',
        'number of pixels'                       => 'número de píxeis',
        'wind force'                             => 'força do vento',
        'power factor'                           => 'fator de potência',
        'smoke obscuration'                      => 'obscurecimento por fumo',
        'global warming potential'               => 'potencial de aquecimento global',
        'ozone depletion potential'              => 'potencial de destruição da camada de ozono',
        'acidification potential'                => 'potencial de acidificação',
        'eutrophication potential'               => 'potencial de eutrofização',
        'photochemical ozone creation potential' => 'potencial de formação de ozono fotoquímico',
        'abiotic depletion potential'            => 'potencial de depleção abiótica',
        'ecotoxicity potential'                  => 'potencial de ecotoxicidade',
        'human toxicity potential'               => 'potencial de toxicidade humana',
        'ionising radiation potential'           => 'potencial de radiação ionizante',
        'biogenic carbon content'                => 'teor de carbono biogénico',
        'mass flux'                              => 'fluxo mássico',
        'molar flux'                             => 'fluxo molar',
        'magnetic moment'                        => 'momento magnético',
        'Seebeck coefficient'                    => 'coeficiente de Seebeck',
    ];

    /** Canonical SI dimension => the quantity it measures unless BY_UNIT says otherwise. */
    public const BY_DIMENSION = [
        '1'               => 'dimensionless ratio',
        'L1'              => 'length',
        'L2'              => 'area',
        'L3'              => 'volume',
        'L4'              => 'second moment of area',
        'M1'              => 'mass',
        'T1'              => 'time',
        'T-1'             => 'frequency',
        'T-2'             => 'angular acceleration',
        'I1'              => 'electric current',
        'Θ1'              => 'thermodynamic temperature',
        'N1'              => 'amount of substance',
        'J1'              => 'luminous intensity',
        'L1T-1'           => 'speed',
        'L1T-2'           => 'acceleration',
        'L2T-1'           => 'kinematic viscosity',
        'L3T-1'           => 'volume flow rate',
        'M1T-1'           => 'mass flow rate',
        'L-3M1'           => 'density',
        'L-2M1'           => 'area density',
        'L-1M1'           => 'linear density',
        'L1M1T-2'         => 'force',
        'L-1M1T-2'        => 'pressure',
        'L-0.5M1T-2'      => 'stress intensity factor',
        'L2M1T-2'         => 'energy',
        'L2M1T-3'         => 'power',
        'L2T-3'           => 'power per mass',
        'L1M1T-3'         => 'power per length',
        'M1T-2'           => 'energy per area',
        'M1T-3'           => 'heat flux density',
        'L2T-2'           => 'specific energy',
        'L2M1T-2Θ-1'      => 'heat capacity',
        'L2T-2Θ-1'        => 'specific heat capacity',
        'L1M1T-3Θ-1'      => 'thermal conductivity',
        'M1T-3Θ-1'        => 'thermal transmittance',
        'M-1T3Θ1'         => 'thermal resistance',
        'L2M1T-3Θ-1'      => 'thermal conductance',
        'Θ-1'             => 'coefficient of thermal expansion',
        'T-1Θ1'           => 'temperature change rate',
        'T1Θ1'            => 'degree days',
        'L2M1T-3I-1'      => 'electric potential difference',
        'L2M1T-3I-2'      => 'electrical resistance',
        'L1M1T-3I-2'      => 'electrical resistance per length',
        'L-2M-1T3I2'      => 'electrical conductance',
        'L-3M-1T3I2'      => 'electrical conductivity',
        'L3M1T-3I-2'      => 'electrical resistivity',
        'T1I1'            => 'electric charge',
        'L-2M-1T4I2'      => 'capacitance',
        'L2M1T-2I-2'      => 'inductance',
        'L2M1T-2I-1'      => 'magnetic flux',
        'M1T-2I-1'        => 'magnetic flux density',
        'L-1I1'           => 'magnetic field strength',
        'L-2I1'           => 'current density',
        'L1M1T-3I-1'      => 'electric field strength',
        'L1T1I1'          => 'electric dipole moment',
        'L-2J1'           => 'illuminance',
        'L-2M-1T3J1'      => 'luminous efficacy',
        'L-3N1'           => 'amount-of-substance concentration',
        'L-2M1T-2'        => 'specific weight',
        'L-1M1T-1'        => 'dynamic viscosity',
        'L-1'             => 'reciprocal length',
        'L-2'             => 'reciprocal area',
        'L2M1'            => 'moment of inertia',
        'L2M1T-1'         => 'angular momentum',
        'L1M1T-1'         => 'momentum',
        'L1M-1T2'         => 'compressibility',
        'L-3M1T-1'        => 'airflow resistivity',
        'L-4M1T-1'        => 'airflow resistance',
        'L3M1T-2'         => 'bending stiffness',
        'L-2M1T-1'        => 'mass flux',
        'L-2T-1N1'        => 'molar flux',
        'L2I1'            => 'magnetic moment',
        'L2M1T-3I-1Θ-1'   => 'Seebeck coefficient',
    ];

    /** Unit code => quantity, where the dimension alone is ambiguous or absent. */
    public const BY_UNIT = [
        // angles and ratios (all dimension "1")
        'rad' => 'plane angle', 'deg' => 'plane angle', '°' => 'plane angle', "'" => 'plane angle', 'degPh' => 'plane angle',
        'sr' => 'solid angle',
        'g/kg' => 'mass fraction', 'mg/kg' => 'mass fraction',
        'mm/m' => 'strain',
        '%RH' => 'relative humidity', 'gH₂O/kgAir' => 'humidity ratio',
        'ppm' => 'dimensionless ratio', 'ppb' => 'dimensionless ratio', 'ppmv' => 'dimensionless ratio',
        'ppu' => 'dimensionless ratio', 'unitless' => 'dimensionless ratio',
        'BTU/Wh' => 'efficiency ratio', 'COP' => 'efficiency ratio', 'DCIE' => 'efficiency ratio', 'PUE' => 'efficiency ratio',
        'pf' => 'power factor',
        // length-dimension and area-dimension ratios
        'cm²/m' => 'area per length', 'mm²/m' => 'area per length', 'm²/m' => 'area per length',
        'l/m' => 'volume per length', 'm³/m' => 'volume per length',
        'kcmil' => 'area',
        // rates
        'Bq' => 'activity', 'rad/s' => 'angular velocity', 'rpm' => 'rotational speed', 'Rpm' => 'rotational speed',
        'ACH' => 'air change rate', 'Fps' => 'frequency', 'c/h' => 'frequency', 'c/min' => 'frequency',
        'At' => 'magnetomotive force', 'lm' => 'luminous flux',
        'cd/m²' => 'luminance', 'cd/ft²' => 'luminance',
        'mg/l' => 'mass concentration', 'g/l' => 'mass concentration', 'mg/m³' => 'mass concentration',
        'µg/m³' => 'mass concentration', 'g/m³' => 'mass concentration',
        'm³/(h·m²) @ 50Pa' => 'air permeability', 'm³/m²h50Pa' => 'air permeability',
        'kg/m/s/Pa' => 'water vapour permeability',
        // mechanics sharing a dimension with something else
        'Nm' => 'torque', 'kN·m/°' => 'rotational stiffness',
        'N/m' => 'force per length', 'N/mm' => 'force per length', 'kN/m' => 'force per length', 'kgf/m' => 'force per length',
        'W/cfm' => 'specific fan power', 'kW/(m³/s)' => 'specific fan power', 'kW/kcfm' => 'specific fan power',
        'W/m² irr' => 'irradiance', 'W/ft² irr' => 'irradiance',
        // no physical dimension
        'GB' => 'information', 'MB' => 'information', 'PB' => 'information', 'TB' => 'information', 'kB' => 'information',
        'kByte' => 'information', 'Mbyte' => 'information', 'byte' => 'information', 'bit' => 'information',
        'bit/s' => 'data rate', 'kBit/s' => 'data rate', 'Mbit/s' => 'data rate', 'Gbit/s' => 'data rate',
        'Gbit/sec' => 'data rate', 'Bd' => 'data rate',
        'dB' => 'sound level', 'dB(A)' => 'sound level', 'dB(C)' => 'sound level', 'dB(A) re 1 pW' => 'sound level',
        'dBi' => 'signal level', 'dBm' => 'signal level', 'dBmV' => 'signal level', 'dBµV' => 'signal level',
        'ntu' => 'turbidity', 'fnu' => 'turbidity',
        '°dH' => 'water hardness', 'PSU' => 'salinity', 'PTV' => 'slip resistance', 'HRC' => 'hardness',
        'DPI' => 'image resolution', 'px' => 'number of pixels', 'Megapixel' => 'number of pixels', 'MP' => 'number of pixels',
        'Bft' => 'wind force',
        '%obsc/ft' => 'smoke obscuration', '%obsc/m' => 'smoke obscuration',
        'kg CO₂ eq' => 'global warming potential',
        'kg CFC11 eq' => 'ozone depletion potential',
        'kg SO2 eq' => 'acidification potential', 'mol H+ eq' => 'acidification potential',
        'kg PO₄³⁻ eq' => 'eutrophication potential', 'kg N eq' => 'eutrophication potential', 'mol N eq' => 'eutrophication potential',
        'kg ethene eq' => 'photochemical ozone creation potential', 'kg NMVOC eq' => 'photochemical ozone creation potential',
        'kg O₃ eq' => 'photochemical ozone creation potential',
        'kg Sb eq' => 'abiotic depletion potential',
        'CTUe' => 'ecotoxicity potential', 'CTUh' => 'human toxicity potential',
        'kBq U235 eq' => 'ionising radiation potential',
        'kg C' => 'biogenic carbon content',
    ];

    /** Three-letter upper-case codes in the bSDD list that are NOT ISO 4217 currencies. */
    private const NOT_CURRENCY = ['COP', 'DPI', 'HRC', 'PSU', 'PTV', 'PUE', 'TPI', 'ACH'];

    /**
     * The quantity a unit measures, or null when it cannot be classified honestly.
     *
     * @param string|null $dimension canonical SI dimension of the unit ({@see SiDimension::canonical()})
     */
    public static function classify(string $code, ?string $dimension): ?string
    {
        if (isset(self::BY_UNIT[$code])) {
            return self::BY_UNIT[$code];
        }
        // ISO 4217 codes: three capitals and no physical dimension (MVA and VAR have one).
        if ($dimension === null && preg_match('/^[A-Z]{3}$/', $code) && !in_array($code, self::NOT_CURRENCY, true)) {
            return 'currency';
        }

        return $dimension !== null ? (self::BY_DIMENSION[$dimension] ?? null) : null;
    }

    /** Portuguese label for a quantity name (falls back to the English name). */
    public static function pt(?string $name): ?string
    {
        return $name === null ? null : (self::NAMES[$name] ?? $name);
    }
}
