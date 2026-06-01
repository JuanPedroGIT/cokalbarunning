-- ============================================================
-- Cokalba Running — Datos iniciales desde R2
-- Ejecutar: docker exec -i cokalbarunning-backend psql -U cokalba -d cokalba_running < datos_iniciales.sql
-- ============================================================

BEGIN;

-- Limpiar datos existentes para carga idempotente
DELETE FROM race_documents;
DELETE FROM sponsors;
DELETE FROM race_editions;

-- ============================================================
-- 1. RACE EDITIONS (2016-2026)
-- ============================================================

-- 2016 (sin poster/camiseta en R2)
INSERT INTO race_editions (id, year, name, description, date, location, is_active, poster_url, shirt_url, registration_url, inscription_info, solidarity_cause, solidarity_url)
VALUES ('e0002016-0000-4000-a000-000000000001', 2016, 'II Carrera Solidaria Cocalba Running', 'II edición de la carrera solidaria', '2016-06-15 10:00:00', 'Coca de Alba', false, NULL, NULL, NULL, NULL, NULL, NULL);

-- 2017
INSERT INTO race_editions (id, year, name, description, date, location, is_active, poster_url, shirt_url, registration_url, inscription_info, solidarity_cause, solidarity_url)
VALUES ('e0002017-0000-4000-a000-000000000002', 2017, 'III Carrera Solidaria Cocalba Running', 'III edición de la carrera solidaria', '2017-06-15 10:00:00', 'Coca de Alba', false, NULL, NULL, NULL, NULL, NULL, NULL);

-- 2018
INSERT INTO race_editions (id, year, name, description, date, location, is_active, poster_url, shirt_url, registration_url, inscription_info, solidarity_cause, solidarity_url)
VALUES ('e0002018-0000-4000-a000-000000000003', 2018, 'IV Carrera Solidaria Cocalba Running', 'IV edición de la carrera solidaria', '2018-06-15 10:00:00', 'Coca de Alba', false, NULL, NULL, NULL, NULL, NULL, NULL);

-- 2019
INSERT INTO race_editions (id, year, name, description, date, location, is_active, poster_url, shirt_url, registration_url, inscription_info, solidarity_cause, solidarity_url)
VALUES ('e0002019-0000-4000-a000-000000000004', 2019, 'V Carrera Solidaria Cocalba Running', 'V edición de la carrera solidaria', '2019-06-15 10:00:00', 'Coca de Alba', false, NULL, NULL, NULL, NULL, NULL, NULL);

-- 2022
INSERT INTO race_editions (id, year, name, description, date, location, is_active, poster_url, shirt_url, registration_url, inscription_info, solidarity_cause, solidarity_url)
VALUES ('e0002022-0000-4000-a000-000000000005', 2022, 'VI Carrera Solidaria Cocalba Running', 'VI edición de la carrera solidaria', '2022-06-15 10:00:00', 'Coca de Alba', false, NULL, NULL, NULL, NULL, NULL, NULL);

-- 2023
INSERT INTO race_editions (id, year, name, description, date, location, is_active, poster_url, shirt_url, registration_url, inscription_info, solidarity_cause, solidarity_url)
VALUES ('e0002023-0000-4000-a000-000000000006', 2023, 'VII Carrera Solidaria Cocalba Running', 'VII edición de la carrera solidaria', '2023-07-02 10:00:00', 'Coca de Alba', false, NULL, NULL, NULL, NULL, NULL, NULL);

-- 2024 (tiene poster y camiseta en R2)
INSERT INTO race_editions (id, year, name, description, date, location, is_active, poster_url, shirt_url, registration_url, inscription_info, solidarity_cause, solidarity_url)
VALUES ('e0002024-0000-4000-a000-000000000007', 2024, 'VIII Carrera Solidaria Cocalba Running', 'VIII edición de la carrera solidaria', '2024-06-30 10:00:00', 'Coca de Alba', false,
        'un-nuevo-impulso/race/2024/docs/poster-d061ab24-b7b4-4091-a6a4-f779f5b3d7bc.jpg',
        'un-nuevo-impulso/race/2024/docs/camiseta-8a402cbf-ebdb-4afe-a81c-df2060c0ee15.jpg',
        NULL, NULL, NULL, NULL);

-- 2025 (tiene poster)
INSERT INTO race_editions (id, year, name, description, date, location, is_active, poster_url, shirt_url, registration_url, inscription_info, solidarity_cause, solidarity_url)
VALUES ('e0002025-0000-4000-a000-000000000008', 2025, 'IX Carrera Solidaria Cocalba Running', 'IX edición de la carrera solidaria', '2025-07-06 10:00:00', 'Coca de Alba', false,
        'un-nuevo-impulso/race/2025/docs/poster-ce55d194-5a37-4b55-a2dd-aecd4f14d4b2.jpg',
        NULL,
        NULL, NULL, NULL, NULL);

-- 2026 (edición activa, poster y camiseta)
INSERT INTO race_editions (id, year, name, description, date, location, is_active, poster_url, shirt_url, registration_url, inscription_info, solidarity_cause, solidarity_url)
VALUES ('e0002026-0000-4000-a000-000000000009', 2026, 'X Carrera Solidaria — Un Nuevo Impulso', 'X edición de la carrera solidaria Cokalba Running', '2026-07-05 10:00:00', 'Coca de Alba', true,
        'un-nuevo-impulso/race/2026/docs/cartel-2026.jpg',
        'un-nuevo-impulso/race/2026/docs/camiseta-2026.jpeg',
        NULL, NULL, NULL, NULL);


-- ============================================================
-- 2. SPONSORS
-- ============================================================

INSERT INTO sponsors (id, name, logo_url, website, tier, is_active, sort_order, message)
VALUES
('s0000000-0000-4000-a000-000000000001', 'Erbe',                     'un-nuevo-impulso/sponsors/erbe.jpg',                           'https://www.erbe.es',               'colaborador',   true, 1,  NULL),
('s0000000-0000-4000-a000-000000000002', 'Diputación de Salamanca',  'un-nuevo-impulso/sponsors/diputacion.png',                     'https://www.diputaciondesalamanca.es','institucional', true, 2,  NULL),
('s0000000-0000-4000-a000-000000000003', 'Coca de Alba',             'un-nuevo-impulso/sponsors/cocadealba.jpg',                     NULL,                                'principal',     true, 3,  NULL),
('s0000000-0000-4000-a000-000000000004', 'Casa Mudayyan',            'un-nuevo-impulso/sponsors/casa-mudayyan.jpg',                  NULL,                                'colaborador',   true, 4,  NULL),
('s0000000-0000-4000-a000-000000000005', 'MAAM',                     'un-nuevo-impulso/sponsors/MAAM.png',                           NULL,                                'colaborador',   true, 5,  NULL),
('s0000000-0000-4000-a000-000000000006', 'Agro Salinero',            'un-nuevo-impulso/sponsors/agro-salinero.jpeg',                 NULL,                                'colaborador',   true, 6,  NULL),
('s0000000-0000-4000-a000-000000000007', 'Bunge',                    'un-nuevo-impulso/sponsors/bunge.jpeg',                         'https://www.bunge.com',             'colaborador',   true, 7,  NULL),
('s0000000-0000-4000-a000-000000000008', 'Transportes Bautista',     'un-nuevo-impulso/sponsors/transportes-bautista.jpeg',          NULL,                                'colaborador',   true, 8,  NULL),
('s0000000-0000-4000-a000-000000000009', 'Hernández Bueno',          'un-nuevo-impulso/sponsors/hernandez-bueno.png',                NULL,                                'colaborador',   true, 9,  NULL),
('s0000000-0000-4000-a000-000000000010', 'Lanmoon',                  'un-nuevo-impulso/sponsors/7dd0916d-48e4-4395-b89e-ef4ab92fcf2e.png', NULL,                          'colaborador',   true, 10, NULL),
('s0000000-0000-4000-a000-000000000011', 'Dulzia Salamanca',         'un-nuevo-impulso/sponsors/bfbcecf4-83f4-4da8-b470-1dc6b80581c0.jpg', NULL,                          'colaborador',   true, 11, NULL),
('s0000000-0000-4000-a000-000000000012', 'Logo Faustino',            'un-nuevo-impulso/sponsors/logo-faustino.jpg.jpeg',             NULL,                                'colaborador',   true, 12, NULL);


-- ============================================================
-- 3. RACE DOCUMENTS (resultados + documentos generales)
-- ============================================================

-- Resultados por edición
INSERT INTO race_documents (id, edition_id, name, type, file_path, created_at)
VALUES
('d0000000-0000-4000-a000-000000000001', 'e0002016-0000-4000-a000-000000000001', 'Clasificación 2016',          'results', 'un-nuevo-impulso/race/2016/results/CLASIFICACION_2016.pdf', '2016-07-15 12:00:00'),
('d0000000-0000-4000-a000-000000000002', 'e0002017-0000-4000-a000-000000000002', 'Clasificación 2017',          'results', 'un-nuevo-impulso/race/2017/results/CLASIFICACION_2017.pdf', '2017-07-15 12:00:00'),
('d0000000-0000-4000-a000-000000000003', 'e0002018-0000-4000-a000-000000000003', 'Clasificación 2018',          'results', 'un-nuevo-impulso/race/2018/results/CLASIFICACION_2018.pdf', '2018-07-15 12:00:00'),
('d0000000-0000-4000-a000-000000000004', 'e0002019-0000-4000-a000-000000000004', 'Clasificación 2019',          'results', 'un-nuevo-impulso/race/2019/results/CLASIFICACION_2019.pdf', '2019-07-15 12:00:00'),
('d0000000-0000-4000-a000-000000000005', 'e0002022-0000-4000-a000-000000000005', 'Clasificación 2022',          'results', 'un-nuevo-impulso/race/2022/results/CLASIFICACION_2022.pdf', '2022-07-15 12:00:00'),
('d0000000-0000-4000-a000-000000000006', 'e0002023-0000-4000-a000-000000000006', 'Clasificación 2023',          'results', 'un-nuevo-impulso/race/2023/results/CLASIFICACION_2023.pdf', '2023-07-15 12:00:00'),
('d0000000-0000-4000-a000-000000000007', 'e0002024-0000-4000-a000-000000000007', 'Clasificación 2024',          'results', 'un-nuevo-impulso/race/2024/results/CLASIFICACION_2024.pdf', '2024-07-15 12:00:00'),
('d0000000-0000-4000-a000-000000000008', 'e0002025-0000-4000-a000-000000000008', 'Clasificación 2025',          'results', 'un-nuevo-impulso/race/2025/results/CLASIFICACION_2025.pdf', '2025-07-15 12:00:00');

-- Documentos generales (sin edición asociada)
INSERT INTO race_documents (id, edition_id, name, type, file_path, created_at)
VALUES
('d0000000-0000-4000-a000-000000000010', NULL, 'Reglamento IX Carrera Solidaria', 'other',   'un-nuevo-impulso/docs/REGLAMENTO_IX_CARRERA_SOLIDARIA_UN_NUEVO_IMPULSO.pdf', '2026-05-01 12:00:00'),
('d0000000-0000-4000-a000-000000000011', NULL, 'Perfil de la Carrera',            'profile', 'un-nuevo-impulso/docs/perfil-carrera.jpeg',                                    '2026-05-01 12:00:00'),
('d0000000-0000-4000-a000-000000000012', NULL, 'Recorrido de la Carrera',         'route',   'un-nuevo-impulso/docs/recorrido-carrera.jpeg',                                 '2026-05-01 12:00:00'),
('d0000000-0000-4000-a000-000000000013', NULL, 'Recorrido Infantil',              'route',   'un-nuevo-impulso/docs/recorrido_niños.pdf',                                    '2026-05-01 12:00:00');

COMMIT;

-- ============================================================
-- Resumen:
--   race_editions: 9 (2016-2026)
--   sponsors:      12
--   race_documents: 12 (8 resultados + 4 generales)
-- ============================================================
