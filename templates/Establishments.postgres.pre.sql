create extension if not exists "uuid-ossp";
create extension if not exists postgis;

GRANT EXECUTE ON ALL FUNCTIONS IN SCHEMA public TO app_anonymous_user, app_registered_user;

DROP TABLE IF EXISTS app_public.fhr_establishment CASCADE;

create table app_public.fhr_establishment (
  uuid                        uuid primary key,
  fhrsid                      integer NOT NULL,
  local_authority_business_id text not null check (char_length(local_authority_business_id) < 80),
  business_name               text not null,
  business_type_id            integer NOT NULL references app_public.fhr_business_type(business_type_id),
  address_line_1              text not null,
  address_line_2              text not null,
  address_line_3              text not null,
  address_line_4              text not null,
  postcode                    text not null check (char_length(postcode) < 10),
  phone                       text not null,
  rating_key                  text NOT NULL references app_public.fhr_rating(rating_key),
  rating_date                 timestamp not null,
  local_authority_code        text NOT NULL references app_public.fhr_authority(local_authority_id_code),
  geocode                     geography(point,4326),
  created_at                  timestamp default now(),
  updated_at                  timestamp default now(),
  CONSTRAINT                  unique_fhr_establishment_fhrsid UNIQUE(fhrsid)
);

CREATE INDEX index_fhr_establishment_geocode ON app_public.fhr_establishment USING GIST(geocode);

