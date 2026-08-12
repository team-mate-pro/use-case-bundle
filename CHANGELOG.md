# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## Polityka wersjonowania

Wersję uznajemy za wydaną dopiero w momencie jej wdrożenia na środowisko produkcyjne. Do tego czasu zmiany gromadzą się w sekcji Unreleased i po deployu na prod trafiają do jednej wspólnej wersji, której zostaje przypisany numer (MAJOR/MINOR/PATCH wg SemVer) oraz data deployu. Tag gitowy (git tag -a X.Y.Z) ustawiamy na commicie, który faktycznie trafił na prod — nie na branch dev/stage.

## [Unreleased]

## [4.0.0] - 2026-08-11

### Changed (BREAKING)

- Zależności Symfony zawężone z `>=7.0` do `^7.4`. Powód nie jest kosmetyczny: linie 7.1, 7.2 i 7.3 mają aktywne security advisories na `symfony/yaml` (`PKSA-v5yj-8nmz-sk2q`, `PKSA-ft77-7h5f-p3r6`, `PKSA-b14r-zh1d-vdrc`) i Composer odmawia ich instalacji. Dotychczasowe `>=7.0` dopuszczało dodatkowo nieprzetestowaną linię Symfony 8.x
- `team-mate-pro/contracts`: `^2.0.0` → `^3.0.0`
- `team-mate-pro/tests-bundle` w `require-dev`: `^1.22` → `^2.0`

`php` pozostaje na `>=8.3` — bundle jest testowany na 8.3, 8.4 i 8.5.
- `@template TResult` w `AbstractRestApiController`, `ResultRendererInterface`, `ResultRestRenderer`, `ResultResponseFactory`, `ResponseAsBlobInterface` i `ResponseAsBlobCsvInterface` ograniczone do `array<int|string, mixed>|object` — wynika z zawężenia generyka `Result<T>` w contracts 3.0.0. Kod przekazujący `Result` bez adnotacji generycznych nie wymaga zmian

### Fixed

- `ResultResponseFactory::createBlobResponse()` deklarował `Result<Stringable>`, mimo że `ResponseAsBlobInterface` przyjmuje dowolny `Result` — niezgodność wariancji, której PHPStan 1.x nie wykrywał. Sygnatura zgodna z interfejsem; sprawdzenie `instanceof Stringable` w ciele metody bez zmian

### Changed

- Środowisko deweloperskie i CI przeniesione na PHP 8.5 (`docker/app/Dockerfile`); PHPUnit `^10.0` → `^11.5`, paratest `^7.0` → `^7.8`, PHPStan 1.x → 2.x
- `BundleWiringTest` dziedziczy po `TeamMatePro\TestsBundle\AbstractKernelTestCase` — przywraca handlery błędów po bootowaniu kernela, przez co PHPUnit 11 nie oznacza testów jako risky

## [3.0.0] - 2026-05-27

### Changed (BREAKING)
- Wymagana wersja `team-mate-pro/contracts` podniesiona do `^2.0.0` — w contracts 2.0.0 usunięty został `Result::with()`; wszystkie wywołania w testach (`tests/Unit/...`, `tests/_Data/MotherObject/ResultMother.php`) przepisane na `withItem()` / `withCollection()`; `ResultMother::successWithData()` i `::created()` mają teraz sygnaturę `array|object $data` (zamiast `mixed`)
- Usunięte testy `with(scalar|null)` w `tests/Unit/UseCase/ResultTest.php` (`testResultIsIterableWithArrayIterator`, `testIterableWithSingleItem`, większość casów `hasContentProvider`) — payload `Result` zawsze musi być teraz `array|object`, więc te scenariusze przestały być reprezentatywne

## [2.4.0] - 2026-05-27

### Changed
- `ResultRestRenderer::render()` deleguje rozpoznawanie typu wpisu w payloadzie do `Result::getDataType()` z contracts 1.5.0; usunięte własne wywołania `get_class()` na pojedynczym obiekcie oraz na pierwszym elemencie tablicy
- Wymagana wersja `team-mate-pro/contracts` podniesiona do `^1.5.0` — udostępnia `Result::map()` oraz `Result::getDataType()`

## [2.3.0] - 2026-05-27

### Added
- `ResultRendererInterface` (`src/Http/RestApi/`) — kontrakt renderowania `Result` do tablicy JSON oraz minimalnej obwiedni odpowiedzi (`renderMandatory()` dla exception listenerów); implementacja: `ResultRestRenderer`
- `HttpStatusCodeResolverInterface` + `HttpStatusCodeResolver` (`src/Http/RestApi/`) — kontrakt i implementacja mapowania `ResultType` na kod HTTP, wyciągnięte z `ResultRestRenderer`

### Changed
- `AbstractRestApiController` korzysta z dwóch wstrzykiwanych serwisów (`ResultRendererInterface`, `HttpStatusCodeResolverInterface`) zamiast wywołań statycznych — iniekcja przez `#[Required]` setter (idiom Symfony dla abstract controllers); klasy nadrzędne dostają serwisy automatycznie z DI, bez modyfikacji konstruktorów
- `ResultRestRenderer` z metodami statycznymi na klasę instancyjną; zależność `HttpStatusCodeResolverInterface` wstrzyknięta przez konstruktor
- `ResultRestRenderer::render()` czyta klucz `item`/`collection` z `Result::getItemType()` (nowe API contracts 1.4.0); fallback na detekcję na podstawie kształtu danych dla legacy `Result::with()`
- `AuthorizationExceptionListener` i `ValidationExceptionListener` przyjmują `ResultRendererInterface` przez konstruktor (wcześniej statyczne `ResultRestRenderer::renderMandatory()`)
- Wymagana wersja `team-mate-pro/contracts` podniesiona do `^1.4.0` — udostępnia nowe API `Result::withItem()` / `Result::withCollection()` / `Result::getItemType()` rozróżniające payload pojedynczy od kolekcji oraz oznacza `Result::with()` jako `@deprecated`
- Usunięty `phpstan-baseline.neon` — wszystkie błędy PHPStan na poziomie `max` poprawione bez supresji

### Removed (BREAKING)
- `ResultRestRenderer::render()` / `getHttpStatusCode()` / `renderMandatory()` jako wywołania statyczne — należy korzystać z serwisów `ResultRendererInterface` i `HttpStatusCodeResolverInterface` (autowire z DI lub ręczne instancjonowanie)

## [2.2.0] - 2026-04-21

### Added
- Licencja MIT w metadanych pakietu

## [2.1.1] - 2026-03-26

### Changed
- Pełne pokrycie testami jednostkowymi (`tests/Unit/`) — 165 testów, 297 asercji
- Podział pipeline CI/CD na osobne joby (statyczna analiza vs. publikacja)
- `phpstan` w shared library static analysis template z `sh/tmp-infra`

### Fixed
- Niepoprawna nazwa komendy `phpcs` w `docker-compose`

## [2.1.0] - 2026-03-24

### Added
- Rozszerzone `ResultType`'y dla pełnego pokrycia kodów statusu HTTP (2xx/4xx/5xx)
- Zgodność z TMP Standards (UCB-001..UCB-005) — udokumentowane reguły architektoniczne use case pattern
- Modernizacja pipeline CI/CD

## [2.0.11] - 2026-02-24

### Fixed
- Niepoprawna nazwa klasy w jednym z eksportowanych typów

## [2.0.9] - 2026-02-24

### Added
- Content negotiation (`ContentTypeChecker`) — rozpoznawanie nagłówka `Accept` dla CSV/PDF i odpowiadające `ResultResponseFactory` budujące właściwy `JsonResponse`/blob

## [2.0.8] - 2025-12-01

### Fixed
- Upload plików z formularzy `multipart/form-data` w `AbstractValidatedRequest`

## [2.0.7] - 2025-11-28

### Changed
- `PartialUpdateService` — pomijanie wartości `Undefined` (sentinel z `team-mate-pro/contracts`) przy mapowaniu DTO → encja, zamiast nadpisywania ich `null`em

## [2.0.6] - 2025-11-28

### Changed
- Normalizacja DTO — priorytet getterów nad bezpośrednim odczytem pól (spójne z DTO interface-driven)

## [2.0.5] - 2025-11-27

### Added
- `AbstractValidatedRequest` jako abstrakcja DTO walidowanego — auto-populacja (JSON body, query, route attrs, multipart), `securityCheck()` przed walidacją, auto-injection `userId`, helper `getValue()` z auto-kastowaniem

## [2.0.4] - 2025-11-20

### Added
- Helpery paginacyjne dla `Result` i kolekcji
- Auto-kastowanie typów prostych (`string`↔`int`↔`float`↔`bool`) w `getValue()`

## [2.0.3] - 2025-11-20

### Added
- Auto-discovery typu Request — Symfony rozpoznaje konkretną klasę po sygnaturze akcji kontrolera

### Fixed
- Stabilizacja testów

## [2.0.2] - 2025-11-07

### Added
- `PatchValidation` — constraint walidujący tylko gdy wartość nie jest `Undefined` (PATCH-style partial updates)

## [2.0.1] - 2025-11-07

### Added
- Interfejsy `CollectionAware` / `ItemAware` rozróżniające payload pojedynczy od kolekcji

## [2.0.0] - 2025-11-07

Pierwszy release linii 2.x — przebudowane fundamenty architekturalne:

### Added
- `AbstractRestApiController` z metodami `response()` / `responseWithCache()` (UCB-004)
- `ResultRestRenderer` mapujący `ResultType` → kody HTTP
- Wzorzec UseCase z `__invoke()` (UCB-002), DTO jako interfejs (UCB-001), `securityCheck()` w Request (UCB-003), sufiks `Action` w kontrolerach (UCB-005)
- Wymuszenie wersji `team-mate-pro/contracts` dla wspólnego `Result` i `ResultType`

[Unreleased]: https://gitlab.team-mate.pl/sh/use-case-bundle/-/compare/3.0.0...HEAD
[3.0.0]: https://gitlab.team-mate.pl/sh/use-case-bundle/-/tags/3.0.0
[2.4.0]: https://gitlab.team-mate.pl/sh/use-case-bundle/-/tags/2.4.0
[2.3.0]: https://gitlab.team-mate.pl/sh/use-case-bundle/-/tags/2.3.0
[2.2.0]: https://gitlab.team-mate.pl/sh/use-case-bundle/-/tags/2.2.0
[2.1.1]: https://gitlab.team-mate.pl/sh/use-case-bundle/-/tags/2.1.1
[2.1.0]: https://gitlab.team-mate.pl/sh/use-case-bundle/-/tags/2.1.0
[2.0.11]: https://gitlab.team-mate.pl/sh/use-case-bundle/-/tags/2.0.11
[2.0.9]: https://gitlab.team-mate.pl/sh/use-case-bundle/-/tags/2.0.9
[2.0.8]: https://gitlab.team-mate.pl/sh/use-case-bundle/-/tags/2.0.8
[2.0.7]: https://gitlab.team-mate.pl/sh/use-case-bundle/-/tags/2.0.7
[2.0.6]: https://gitlab.team-mate.pl/sh/use-case-bundle/-/tags/2.0.6
[2.0.5]: https://gitlab.team-mate.pl/sh/use-case-bundle/-/tags/2.0.5
[2.0.4]: https://gitlab.team-mate.pl/sh/use-case-bundle/-/tags/2.0.4
[2.0.3]: https://gitlab.team-mate.pl/sh/use-case-bundle/-/tags/2.0.3
[2.0.2]: https://gitlab.team-mate.pl/sh/use-case-bundle/-/tags/2.0.2
[2.0.1]: https://gitlab.team-mate.pl/sh/use-case-bundle/-/tags/2.0.1
[2.0.0]: https://gitlab.team-mate.pl/sh/use-case-bundle/-/tags/2.0.0
