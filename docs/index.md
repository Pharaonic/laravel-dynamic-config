---
name: Dynamic Config

action:
  label: View on Packagist
  href: "{package.packagistUrl}"

views: components.packages

breadcrumbs:
  - label: Home
    href: route:home
  - label: Packages
    href: route:packages.index
  - label: "{technology.name} Packages"
    href: "url:/packages/{technology.slug}"
  - label: "{package.name}"

card:
  topic: configuration
  icon: database
  tags: config configuration dynamic config database config settings runtime config multi-tenant tenant branch scopes overrides cache
  description: Store Laravel configuration values in the database, globally or per company, branch or tenant, and merge them into config() with one explicit load call.

seo:
  title: "{package.fullName} - Database-Backed Config Package"
  description: "{package.name} is a Laravel package that stores config values in the database, globally or per identifier, and loads them into config() on demand. {package.downloadsShort}+ downloads, {package.license} licensed."
  keywords: laravel dynamic config, laravel database config, laravel settings, runtime config, multi-tenant config, config overrides, laravel package
  author: Pharaonic
  images:
    - "{package.cover}"
  openGraph:
    type: website
    siteName: Pharaonic
  twitter:
    card: summary_large_image

schema:
  "@type": SoftwareSourceCode
  name: "{package.name}"
  description: "{package.name} is a Laravel package that stores configuration values in the database, globally or per identifier, and merges them into Laravel's config repository on demand."
  image: "{package.cover}"
  codeRepository: "{package.githubUrl}"
  programmingLanguage: PHP
  runtimePlatform: "{technology.name}"
  version: "{package.version}"
  datePublished: "{package.publishedAt}"
  dateModified: "{package.updatedAt}"
  license: "https://opensource.org/licenses/{package.license}"
  isAccessibleForFree: true
  sameAs:
    - "{package.githubUrl}"
    - "{package.packagistUrl}"
  author:
    "@id": url:/#organization
  publisher:
    "@id": url:/#organization
  interactionStatistic:
    "@type": InteractionCounter
    interactionType: https://schema.org/DownloadAction
    userInteractionCount: "{package.downloads}"
---
