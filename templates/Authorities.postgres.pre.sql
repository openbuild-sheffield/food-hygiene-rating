create extension if not exists "uuid-ossp";

DROP TABLE IF EXISTS app_public.fhr_authority CASCADE;

create table app_public.fhr_authority (
  uuid                    uuid primary key,
  local_authority_id      integer NOT NULL,
  local_authority_id_code text not null check (char_length(local_authority_id_code) < 80),
  name                    text check (char_length(name) < 80),
  friendly_name           text check (char_length(friendly_name) < 80),
  url                     text,
  email                   text,
  region_id               integer NOT NULL references app_public.fhr_region(id),
  scheme_type_id          integer NOT NULL references app_public.fhr_scheme_type(scheme_type_id),
  created_at              timestamp default now(),
  updated_at              timestamp default now(),
  CONSTRAINT              unique_fhr_authority_local_authority_id UNIQUE(local_authority_id),
  CONSTRAINT              unique_fhr_authority_local_authority_id_code UNIQUE(local_authority_id_code)
);

comment on table app_public.fhr_authority is
'Food Hygiene Rating Scheme Authorities.';
comment on column app_public.fhr_authority.uuid is
'The primary unique identifier for the food hygiene rating authority.';
comment on column app_public.fhr_authority.local_authority_id is
'The unique identifier for the food hygiene rating authority as provided by http://api.ratings.food.gov.uk.';
comment on column app_public.fhr_authority.local_authority_id_code is
'Another unique identifier for the food hygiene rating authority as provided by http://api.ratings.food.gov.uk.';
comment on column app_public.fhr_authority.name is
'The authority name.';
comment on column app_public.fhr_authority.friendly_name is
'The authority friendly name for links.';
comment on column app_public.fhr_authority.url is
'The authority website.';
comment on column app_public.fhr_authority.email is
'The authority email address.';
comment on column app_public.fhr_authority.region_id is
'The authority region_id links to app_public.fhr_region.id';
comment on column app_public.fhr_authority.scheme_type_id is
'The authority scheme_type_id links to app_public.fhr_scheme_type.scheme_type_id';
comment on column app_public.fhr_authority.created_at is
'The time this scheme was created.';
comment on column app_public.fhr_authority.updated_at is
'The time this scheme was updated.';

