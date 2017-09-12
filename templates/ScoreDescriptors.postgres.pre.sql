create extension if not exists "uuid-ossp";

DROP TABLE IF EXISTS app_public.fhr_score_category CASCADE;
DROP TABLE IF EXISTS app_public.fhr_score_category_score CASCADE;

create table app_public.fhr_score_category (
  uuid           uuid primary key,
  score_category text not null check (char_length(score_category) < 80),
  created_at     timestamp default now(),
  updated_at     timestamp default now(),
  CONSTRAINT     unique_fhr_score_category_score_category_key UNIQUE(score_category)
);

create table app_public.fhr_score_category_score (
  uuid                uuid primary key,
  score_category_uuid uuid not null references app_public.fhr_score_category(uuid),
  score               integer not null,
  description         text not null check (char_length(description) < 80),
  created_at          timestamp default now(),
  updated_at          timestamp default now(),
  CONSTRAINT          unique_fhr_score_category_score_key UNIQUE(score_category_uuid, score)
);

comment on table app_public.fhr_score_category is
'Food Hygiene Rating Score Categories.';
comment on column app_public.fhr_score_category.uuid is
'The primary unique identifier for the food hygiene score category.';
comment on column app_public.fhr_score_category.score_category is
'The food hygiene score category.';
comment on column app_public.fhr_score_category.created_at is
'The time this scheme was created.';
comment on column app_public.fhr_score_category.updated_at is
'The time this scheme was updated.';

comment on table app_public.fhr_score_category_score is
'Food Hygiene Rating Score Categories Scores.';
comment on column app_public.fhr_score_category_score.uuid is
'The primary unique identifier for the food hygiene score category score.';
comment on column app_public.fhr_score_category_score.score_category_uuid is
'The primary unique identifier for the food hygiene score category links to app_public.fhr_score_category.uuid.';
comment on column app_public.fhr_score_category_score.score is
'The food hygiene score .';
comment on column app_public.fhr_score_category_score.description is
'The food hygiene score description.';
comment on column app_public.fhr_score_category_score.created_at is
'The time this scheme was created.';
comment on column app_public.fhr_score_category_score.updated_at is
'The time this scheme was updated.';

