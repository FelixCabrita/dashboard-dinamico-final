<?php

namespace App\Libraries\ETL;

/**
 * DataNormalizer
 * Librería para normalizar y limpiar datos de los CSV
 */
class DataNormalizer
{
    /**
     * Cache de dimensiones (municipios, especies, formaciones)
     *
     * @var array
     */
    protected $dimensions = [
        'municipios' => [],
        'especies' => [],
        'formaciones_arboladas' => [],
        'formaciones_herbaceas' => [],
        'formaciones_matorral' => [],
    ];

    /**
     * IDs autoincrementales para dimensiones
     *
     * @var array
     */
    protected $dimensionIds = [
        'municipios' => 1,
        'especies' => 1,
        'formaciones_arboladas' => 1,
        'formaciones_herbaceas' => 1,
        'formaciones_matorral' => 1,
    ];

    /**
     * Normalizar datos según el archivo CSV
     *
     * @param string $fileName
     * @param array $data
     * @return array
     */
    public function normalize(string $fileName, array $data): array
    {
        $method = $this->getMethodForFile($fileName);

        if (method_exists($this, $method)) {
            return $this->$method($data);
        }

        // Si no hay método específico, hacer normalización básica
        return $this->normalizeBasic($data);
    }

    /**
     * Obtener método de normalización según nombre de archivo
     *
     * @param string $fileName
     * @return string
     */
    protected function getMethodForFile(string $fileName): string
    {
        $mapping = [
            'Existencias_Muni_BMP_4.csv' => 'normalizeExistencias',
            'BMP_ExsMuniIFN2.csv' => 'normalizeIFN2',
            'BMP_ExsMuniIFN3.csv' => 'normalizeIFN3',
            'MFE25_FormArb.csv' => 'normalizeFormArb',
            'MFE25_FormHerb.csv' => 'normalizeFormHerb',
            'MFE25_FormMato.csv' => 'normalizeFormMato',
            'MFE25_LULUCF.csv' => 'normalizeLULUCF',
            'MFE25_ModCom.csv' => 'normalizeModCom',
            'MFE50_FormacionesArboladas_Mun.csv' => 'normalizeMFE50',
            'Prop_BMP_Mun.csv' => 'normalizeProp',
            'ValorMUNI_BMP.csv' => 'normalizeValor',
            'CalidadesMUNI.csv' => 'normalizeCalidades',
        ];

        return $mapping[$fileName] ?? 'normalizeBasic';
    }

    /**
     * Normalizar Existencias_Muni_BMP_4.csv
     *
     * @param array $data
     * @return array
     */
    protected function normalizeExistencias(array $data): array
    {
        $normalized = [];

        foreach ($data as $row) {
            $municipioId = $this->getMunicipioId($row['IDmuni'] ?? '');
            $especieId = $this->getEspecieId($row['Especie'] ?? '');

            $normalized[] = [
                'municipio_id' => $municipioId,
                'especie_id' => $especieId,
                'clase_diametro' => $this->toInt($row['CD'] ?? 0),
                'npies' => $this->toFloat($row['NPies_sum'] ?? 0),
                'abas' => $this->toFloat($row['ABas_sum'] ?? 0),
                'vcc' => $this->toFloat($row['VCC_sum'] ?? 0),
                'vsc' => $this->toFloat($row['VSC_sum'] ?? 0),
                'iavc' => $this->toFloat($row['IAVC_sum'] ?? 0),
                'vle' => $this->toFloat($row['VLE_sum'] ?? 0),
                'ba' => $this->toFloat($row['BA_sum'] ?? 0),
                'br' => $this->toFloat($row['BR_sum'] ?? 0),
                'ca' => $this->toFloat($row['CA_sum'] ?? 0),
                'cr' => $this->toFloat($row['CR_sum'] ?? 0),
            ];
        }

        return $normalized;
    }

    /**
     * Normalizar BMP_ExsMuniIFN2.csv
     *
     * @param array $data
     * @return array
     */
    protected function normalizeIFN2(array $data): array
    {
        return $this->normalizeIFNCommon($data, 2);
    }

    /**
     * Normalizar BMP_ExsMuniIFN3.csv
     *
     * @param array $data
     * @return array
     */
    protected function normalizeIFN3(array $data): array
    {
        return $this->normalizeIFNCommon($data, 3);
    }

    /**
     * Normalización común para IFN2 e IFN3
     *
     * @param array $data
     * @param int $version
     * @return array
     */
    protected function normalizeIFNCommon(array $data, int $version): array
    {
        $normalized = [];

        foreach ($data as $row) {
            $municipioId = $this->getMunicipioId($row['IDmuni'] ?? '');
            $especieId = $this->getEspecieId($row['Especie'] ?? '');

            $normalized[] = [
                'ifn_version' => $version,
                'municipio_id' => $municipioId,
                'especie_id' => $especieId,
                'clase_diametro' => $this->toInt($row['CD'] ?? 0),
                'npies' => $this->toFloat($row['NPies_sum'] ?? 0),
                'abas' => $this->toFloat($row['ABas_sum'] ?? 0),
                'vcc' => $this->toFloat($row['VCC_sum'] ?? 0),
            ];
        }

        return $normalized;
    }

    /**
     * Normalizar MFE25_FormArb.csv
     *
     * @param array $data
     * @return array
     */
    protected function normalizeFormArb(array $data): array
    {
        $normalized = [];

        foreach ($data as $row) {
            $municipioId = $this->getMunicipioId($row['IDmuni'] ?? '');
            $formacionId = $this->getFormacionArboladaId(
                $row['FORARB'] ?? '',
                $row['FormArbNOM'] ?? ''
            );

            $normalized[] = [
                'municipio_id' => $municipioId,
                'formacion_id' => $formacionId,
                'estrato_ifn' => $this->toString($row['ESTRATOIFN'] ?? ''),
                'area_ha' => $this->toFloat($row['Area_ha'] ?? 0),
                'porcentaje' => $this->toFloat($row['Perc'] ?? 0),
            ];
        }

        return $normalized;
    }

    /**
     * Normalizar MFE25_FormHerb.csv
     *
     * @param array $data
     * @return array
     */
    protected function normalizeFormHerb(array $data): array
    {
        $normalized = [];

        foreach ($data as $row) {
            $municipioId = $this->getMunicipioId($row['IDmuni'] ?? '');

            $normalized[] = [
                'municipio_id' => $municipioId,
                'formacion_codigo' => $this->toString($row['FORHERB'] ?? ''),
                'formacion_nombre' => $this->toString($row['FormHerbNOM'] ?? ''),
                'area_ha' => $this->toFloat($row['Area_ha'] ?? 0),
                'porcentaje' => $this->toFloat($row['Perc'] ?? 0),
            ];
        }

        return $normalized;
    }

    /**
     * Normalizar MFE25_FormMato.csv
     *
     * @param array $data
     * @return array
     */
    protected function normalizeFormMato(array $data): array
    {
        $normalized = [];

        foreach ($data as $row) {
            $municipioId = $this->getMunicipioId($row['IDmuni'] ?? '');

            $normalized[] = [
                'municipio_id' => $municipioId,
                'formacion_codigo' => $this->toString($row['FORMATO'] ?? ''),
                'formacion_nombre' => $this->toString($row['FormMatoNOM'] ?? ''),
                'area_ha' => $this->toFloat($row['Area_ha'] ?? 0),
                'porcentaje' => $this->toFloat($row['Perc'] ?? 0),
            ];
        }

        return $normalized;
    }

    /**
     * Normalizar MFE25_LULUCF.csv
     *
     * @param array $data
     * @return array
     */
    protected function normalizeLULUCF(array $data): array
    {
        $normalized = [];

        foreach ($data as $row) {
            $municipioId = $this->getMunicipioId($row['IDmuni'] ?? '');

            $normalized[] = [
                'municipio_id' => $municipioId,
                'lulucf_codigo' => $this->toString($row['LULUCF'] ?? ''),
                'lulucf_nombre' => $this->toString($row['LULUCFNOM'] ?? ''),
                'area_ha' => $this->toFloat($row['Area_ha'] ?? 0),
                'porcentaje' => $this->toFloat($row['Perc'] ?? 0),
            ];
        }

        return $normalized;
    }

    /**
     * Normalizar MFE25_ModCom.csv
     *
     * @param array $data
     * @return array
     */
    protected function normalizeModCom(array $data): array
    {
        $normalized = [];

        foreach ($data as $row) {
            $municipioId = $this->getMunicipioId($row['IDmuni'] ?? '');

            $normalized[] = [
                'municipio_id' => $municipioId,
                'modelo_combustible' => $this->toString($row['MODCOM'] ?? ''),
                'modelo_nombre' => $this->toString($row['ModComNOM'] ?? ''),
                'area_ha' => $this->toFloat($row['Area_ha'] ?? 0),
                'porcentaje' => $this->toFloat($row['Perc'] ?? 0),
            ];
        }

        return $normalized;
    }

    /**
     * Normalizar MFE50_FormacionesArboladas_Mun.csv
     *
     * @param array $data
     * @return array
     */
    protected function normalizeMFE50(array $data): array
    {
        return $this->normalizeFormArb($data); // Misma estructura
    }

    /**
     * Normalizar Prop_BMP_Mun.csv
     *
     * @param array $data
     * @return array
     */
    protected function normalizeProp(array $data): array
    {
        $normalized = [];

        foreach ($data as $row) {
            $municipioId = $this->getMunicipioId($row['IDmuni'] ?? '');

            $normalized[] = [
                'municipio_id' => $municipioId,
                'propiedad_codigo' => $this->toString($row['PROPIEDAD'] ?? ''),
                'propiedad_nombre' => $this->toString($row['PropNOM'] ?? ''),
                'area_ha' => $this->toFloat($row['Area_ha'] ?? 0),
                'porcentaje' => $this->toFloat($row['Perc'] ?? 0),
            ];
        }

        return $normalized;
    }

    /**
     * Normalizar ValorMUNI_BMP.csv
     *
     * @param array $data
     * @return array
     */
    protected function normalizeValor(array $data): array
    {
        $normalized = [];

        foreach ($data as $row) {
            $municipioId = $this->getMunicipioId($row['IDmuni'] ?? '');

            $normalized[] = [
                'municipio_id' => $municipioId,
                'valor_total' => $this->toFloat($row['ValorTotal'] ?? 0),
                'valor_medio' => $this->toFloat($row['ValorMedio'] ?? 0),
            ];
        }

        return $normalized;
    }

    /**
     * Normalizar CalidadesMUNI.csv
     *
     * @param array $data
     * @return array
     */
    protected function normalizeCalidades(array $data): array
    {
        $normalized = [];

        foreach ($data as $row) {
            $municipioId = $this->getMunicipioId($row['IDmuni'] ?? '');

            $normalized[] = [
                'municipio_id' => $municipioId,
                'calidad_estacion' => $this->toString($row['CalidadEstacion'] ?? ''),
                'calidad_valor' => $this->toFloat($row['CalidadValor'] ?? 0),
            ];
        }

        return $normalized;
    }

    /**
     * Normalización básica (para archivos sin método específico)
     *
     * @param array $data
     * @return array
     */
    protected function normalizeBasic(array $data): array
    {
        return array_map(function($row) {
            return array_map(function($value) {
                return $this->cleanValue($value);
            }, $row);
        }, $data);
    }

    /**
     * Obtener o crear ID de municipio
     *
     * @param string $codigoIne
     * @return int
     */
    protected function getMunicipioId(string $codigoIne): int
    {
        $codigoIne = $this->toString($codigoIne);

        if (!isset($this->dimensions['municipios'][$codigoIne])) {
            $this->dimensions['municipios'][$codigoIne] = $this->dimensionIds['municipios']++;
        }

        return $this->dimensions['municipios'][$codigoIne];
    }

    /**
     * Obtener o crear ID de especie
     *
     * @param string $codigoEspecie
     * @return int
     */
    protected function getEspecieId(string $codigoEspecie): int
    {
        $codigoEspecie = $this->toString($codigoEspecie);

        if (!isset($this->dimensions['especies'][$codigoEspecie])) {
            $this->dimensions['especies'][$codigoEspecie] = $this->dimensionIds['especies']++;
        }

        return $this->dimensions['especies'][$codigoEspecie];
    }

    /**
     * Obtener o crear ID de formación arbolada
     *
     * @param string $codigo
     * @param string $nombre
     * @return int
     */
    protected function getFormacionArboladaId(string $codigo, string $nombre): int
    {
        $key = $this->toString($codigo);

        if (!isset($this->dimensions['formaciones_arboladas'][$key])) {
            $this->dimensions['formaciones_arboladas'][$key] = [
                'id' => $this->dimensionIds['formaciones_arboladas']++,
                'nombre' => $nombre
            ];
        }

        return $this->dimensions['formaciones_arboladas'][$key]['id'];
    }

    /**
     * Convertir a entero
     *
     * @param mixed $value
     * @return int
     */
    protected function toInt($value): int
    {
        if ($value === '' || $value === null) {
            return 0;
        }

        return (int) $value;
    }

    /**
     * Convertir a float
     *
     * @param mixed $value
     * @return float
     */
    protected function toFloat($value): float
    {
        if ($value === '' || $value === null) {
            return 0.0;
        }

        // Reemplazar coma por punto
        $value = str_replace(',', '.', $value);

        return (float) $value;
    }

    /**
     * Convertir a string limpio
     *
     * @param mixed $value
     * @return string
     */
    protected function toString($value): string
    {
        if ($value === null) {
            return '';
        }

        return trim((string) $value);
    }

    /**
     * Limpiar valor
     *
     * @param mixed $value
     * @return mixed
     */
    protected function cleanValue($value)
    {
        if (is_string($value)) {
            $value = trim($value);

            // Eliminar caracteres especiales problemáticos
            $value = str_replace(["\r", "\n", "\t"], ' ', $value);

            // Normalizar espacios múltiples
            $value = preg_replace('/\s+/', ' ', $value);

            return $value;
        }

        return $value;
    }

    /**
     * Obtener dimensiones construidas
     *
     * @return array
     */
    public function getDimensions(): array
    {
        return $this->dimensions;
    }
}
