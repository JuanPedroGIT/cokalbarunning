DELETE FROM results;
DELETE FROM runners;
DELETE FROM photos;
DELETE FROM blog_posts;
DELETE FROM categories;
DELETE FROM sponsors;
DELETE FROM race_editions;

-- Race Edition 2026
INSERT INTO race_editions (id, year, name, description, date, location, is_active, poster_url, registration_url)
VALUES ('550e8400-e29b-41d4-a716-446655440001', 2026, 'IX Carrera Solidaria Un Nuevo Impulso', 'IX Edición de la carrera solidaria organizada por el Club de Atletismo Coca de Alba. Recorrido mixto asfalto-tierra por el entorno natural del municipio.', '2026-09-20 09:00:00', 'Coca de Alba, Salamanca', true, NULL, 'https://inscripciones.cokalbarunning.es');

-- Categories
INSERT INTO categories (id, name, min_age, max_age, distance_km, gender, race_edition_id)
VALUES
  ('550e8400-e29b-41d4-a716-446655440101', 'Senior Masculino', 18, 34, 10.5, 'M', '550e8400-e29b-41d4-a716-446655440001'),
  ('550e8400-e29b-41d4-a716-446655440102', 'Senior Femenino', 18, 34, 10.5, 'F', '550e8400-e29b-41d4-a716-446655440001'),
  ('550e8400-e29b-41d4-a716-446655440103', 'Veterano A Masculino', 35, 44, 10.5, 'M', '550e8400-e29b-41d4-a716-446655440001'),
  ('550e8400-e29b-41d4-a716-446655440104', 'Veterano A Femenino', 35, 44, 10.5, 'F', '550e8400-e29b-41d4-a716-446655440001'),
  ('550e8400-e29b-41d4-a716-446655440105', 'Veterano B Masculino', 45, 54, 10.5, 'M', '550e8400-e29b-41d4-a716-446655440001'),
  ('550e8400-e29b-41d4-a716-446655440106', 'Veterano B Femenino', 45, 54, 10.5, 'F', '550e8400-e29b-41d4-a716-446655440001'),
  ('550e8400-e29b-41d4-a716-446655440107', 'Junior Masculino', 16, 17, 5.0, 'M', '550e8400-e29b-41d4-a716-446655440001'),
  ('550e8400-e29b-41d4-a716-446655440108', 'Junior Femenino', 16, 17, 5.0, 'F', '550e8400-e29b-41d4-a716-446655440001');

-- Sponsors
INSERT INTO sponsors (id, name, logo_url, website, tier, is_active, sort_order)
VALUES
  ('550e8400-e29b-41d4-a716-446655440201', 'Ayuntamiento de Coca de Alba', NULL, 'https://cocalba.es', 'platinum', true, 1),
  ('550e8400-e29b-41d4-a716-446655440202', 'Diputación de Salamanca', NULL, 'https://salamanca.es', 'platinum', true, 2),
  ('550e8400-e29b-41d4-a716-446655440203', 'Caja Rural', NULL, 'https://cajarural.com', 'gold', true, 3),
  ('550e8400-e29b-41d4-a716-446655440204', 'Deportes Martínez', NULL, 'https://deportesmartinez.com', 'gold', true, 4),
  ('550e8400-e29b-41d4-a716-446655440205', 'Restaurante El Rincón', NULL, 'https://elrincon.es', 'silver', true, 5),
  ('550e8400-e29b-41d4-a716-446655440206', 'Farmacia López', NULL, NULL, 'bronze', true, 6);

-- Blog Posts
INSERT INTO blog_posts (id, title, slug, excerpt, content, tag, published_at, cover_image, created_at)
VALUES
  ('550e8400-e29b-41d4-a716-446655440301', 'Presentamos la IX Edición de Un Nuevo Impulso', 'presentamos-ix-edicion-un-nuevo-impulso', 'Anunciamos la novena edición de nuestra carrera solidaria con importantes novedades.', 'El Club de Atletismo Coca de Alba tiene el placer de anunciar la novena edición de la Carrera Solidaria "Un Nuevo Impulso". Este año volvemos con más fuerza que nunca, con un recorrido mejorado y nuevas categorías para todos los participantes.', 'noticias', '2026-05-01 10:00:00', NULL, NOW()),
  ('550e8400-e29b-41d4-a716-446655440302', 'Consejos de entrenamiento para principiantes', 'consejos-entrenamiento-principiantes', 'Guía básica para quienes se inician en el running.', 'Si te estás iniciando en el running, estos consejos te serán de gran ayuda. Empieza poco a poco, no te exijas de más y disfruta del proceso. Lo más importante es la constancia, no la velocidad.', 'entrenamiento', '2026-05-10 09:00:00', NULL, NOW()),
  ('550e8400-e29b-41d4-a716-446655440303', 'Los patrocinadores que hacen posible este evento', 'patrocinadores-evento-2026', 'Agradecimiento a todos los patrocinadores de la edición 2026.', 'Queremos agradecer a todas las empresas y entidades que colaboran con nosotros. Sin su apoyo, la Carrera Solidaria "Un Nuevo Impulso" no sería posible.', 'patrocinadores', '2026-05-15 11:00:00', NULL, NOW());

-- Photos
INSERT INTO photos (id, filename, thumbnail_filename, caption, is_featured, sort_order, race_edition_id)
VALUES
  ('550e8400-e29b-41d4-a716-446655440401', 'hero-2025.jpg', NULL, 'Salida de la carrera 2025', true, 1, '550e8400-e29b-41d4-a716-446655440001'),
  ('550e8400-e29b-41d4-a716-446655440402', 'finish-2025.jpg', NULL, 'Llegada a meta', true, 2, '550e8400-e29b-41d4-a716-446655440001'),
  ('550e8400-e29b-41d4-a716-446655440403', 'group-2025.jpg', NULL, 'Participantes en el recorrido', false, 3, '550e8400-e29b-41d4-a716-446655440001');

-- Runners
INSERT INTO runners (id, first_name, last_name, email, club, birth_date, gender)
VALUES
  ('550e8400-e29b-41d4-a716-446655440601', 'Antonio', 'García López', 'antonio@example.com', 'Cokalba Running', '1990-03-15', 'M'),
  ('550e8400-e29b-41d4-a716-446655440602', 'Luis', 'Martínez Sánchez', 'luis@example.com', 'Atletismo Salamanca', '1988-07-22', 'M'),
  ('550e8400-e29b-41d4-a716-446655440603', 'María', 'Fernández Ruiz', 'maria@example.com', 'Cokalba Running', '1992-11-08', 'F'),
  ('550e8400-e29b-41d4-a716-446655440604', 'Pedro', 'Hernández Díaz', 'pedro@example.com', 'Runners Valladolid', '1985-01-30', 'M'),
  ('550e8400-e29b-41d4-a716-446655440605', 'Carmen', 'López Martín', 'carmen@example.com', 'Atletismo Segovia', '1989-05-12', 'F');

-- Results
INSERT INTO results (id, bib_number, finish_time_seconds, "position", gender_position, category_position, race_edition_id, runner_id, category_id)
VALUES
  ('550e8400-e29b-41d4-a716-446655440501', '1', 1935, 1, 1, 1, '550e8400-e29b-41d4-a716-446655440001', '550e8400-e29b-41d4-a716-446655440601', '550e8400-e29b-41d4-a716-446655440101'),
  ('550e8400-e29b-41d4-a716-446655440502', '2', 2022, 2, 2, 2, '550e8400-e29b-41d4-a716-446655440001', '550e8400-e29b-41d4-a716-446655440602', '550e8400-e29b-41d4-a716-446655440101'),
  ('550e8400-e29b-41d4-a716-446655440503', '5', 2290, 3, 1, 1, '550e8400-e29b-41d4-a716-446655440001', '550e8400-e29b-41d4-a716-446655440603', '550e8400-e29b-41d4-a716-446655440102'),
  ('550e8400-e29b-41d4-a716-446655440504', '10', 2120, 4, 3, 1, '550e8400-e29b-41d4-a716-446655440001', '550e8400-e29b-41d4-a716-446655440604', '550e8400-e29b-41d4-a716-446655440103'),
  ('550e8400-e29b-41d4-a716-446655440505', '15', 2465, 5, 2, 1, '550e8400-e29b-41d4-a716-446655440001', '550e8400-e29b-41d4-a716-446655440605', '550e8400-e29b-41d4-a716-446655440104');
