# Sembot Helpdesk Lite

A modern helpdesk application built with Laravel 11 (API) and Angular 20 (Frontend).

## Quick Start

### Prerequisites

- Docker and Docker Compose
- Git

### Running the Application

#### Production Mode

Start both API and Frontend services:

```bash
./start.sh
```

This will:
- Create the shared Docker network
- Start the Laravel API (via Sail) on `http://localhost:80`
- Start the Angular Frontend on `http://localhost:4200`

Stop all services:

```bash
./stop.sh
```

Restart all services:

```bash
./restart.sh
```

#### Development Mode (with Hot Reload)

Start both services in development mode:

```bash
./start-dev.sh
```

This will start the frontend with hot reload enabled for development.

Stop development services:

```bash
./stop-dev.sh
```

## Project Structure

```
├── api/              # Laravel 11 API (with Sail)
├── frontend/         # Angular 20 Application
├── start.sh          # Start all services (production)
├── stop.sh           # Stop all services
├── restart.sh        # Restart all services
├── start-dev.sh      # Start all services (development mode)
└── stop-dev.sh       # Stop development services
```

## Services

- **API**: Laravel 11 REST API running on port 80
- **Frontend**: Angular 20 SPA running on port 4200

## Manual Management

### API (Laravel Sail)

```bash
cd api
./vendor/bin/sail up -d    # Start
./vendor/bin/sail down     # Stop
./vendor/bin/sail logs -f  # View logs
```

### Frontend (Docker Compose)

Production mode:
```bash
cd frontend
docker-compose up -d         # Start
docker-compose down          # Stop
docker-compose logs -f       # View logs
```

Development mode:
```bash
cd frontend
docker-compose --profile dev up frontend-dev     # Start with hot reload
docker-compose --profile dev down                # Stop
```

## Development

See individual README files in `api/` and `frontend/` directories for detailed development instructions.


## LLM Flow

### Jak korzystałem z LLM

1. Wykorzystanie LLM miało miejsce od etapu analizy. Na claude.ai stworzyłem prompt dla roli analityka i architekta aby przeprowadzić analizę wymagań i zbudować plan działania
```
    You are an experienced IT Architect and Business Analyst specializing in translating client requirements into actionable development plans. Your role is to analyze project documentation and transform it into a structured, AI-coding-assistant-friendly task breakdown.

    ## Your Responsibilities

    1. **Analyze** the provided client documentation thoroughly
    2. **Identify** key features, requirements, and technical constraints
    3. **Design** a logical implementation sequence
    4. **Create** a hierarchical task structure with Epics and Tasks
    5. **Optimize** the breakdown for AI coding assistants (Claude Code, GitHub Copilot, Cursor, etc.)

    ## Output Format

    Structure your response as follows:

    ### Project Overview
    - Brief summary of the project
    - Key technologies and frameworks
    - Main objectives

    ### Epic Breakdown
    For each Epic, provide:
    - **Epic Name**: Clear, descriptive title
    - **Description**: What this epic accomplishes
    - **Priority**: High/Medium/Low
    - **Dependencies**: What must be completed first

    ### Task List
    For each task within epics:
    - **Task ID**: Epic-specific numbering (e.g., EPIC1-001)
    - **Task Title**: Clear, action-oriented (starts with a verb)
    - **Description**: Detailed enough for an AI coding assistant to understand context
    - **Acceptance Criteria**: Specific, testable conditions
    - **Technical Notes**: Key implementation details, potential gotchas
    - **Estimated Complexity**: Small/Medium/Large
    - **Files to Create/Modify**: Specific file paths when applicable

    ## Guidelines for AI-Friendly Tasks

    - Each task should be **atomic** - completable in one focused coding session
    - Include **specific file paths** and **component names** when known
    - Reference **exact technologies** and **versions** from documentation
    - Highlight **integration points** between tasks
    - Note any **testing requirements** explicitly
    - Flag tasks that require **manual verification** or **configuration**

    ## Example Structure
    ```
    EPIC 1: User Authentication System
    Priority: High
    Dependencies: None

    EPIC1-001: Set up authentication database schema
    - Create users table with email, password_hash, created_at, updated_at
    - Create sessions table for token management
    - Files: database/migrations/create_users_table.php
    - Complexity: Small

    EPIC1-002: Implement user registration endpoint
    - POST /api/register with email/password validation
    - Hash passwords using bcrypt
    - Return JWT token on success
    - Files: app/Http/Controllers/AuthController.php, routes/api.php
    - Complexity: Medium
```

Please analyze the documentation and provide a complete task breakdown optimized for AI-assisted development.
```

2. Przeprowadziłem analizę zadania i wymagań w celu stworzenia dokumentacji projektu, którą dalej wykorzystywałem przy promptowaniu Copilota
- recruitment_task.md - z treścią zadania
- project_brief.md - ze streszczeniem aplikacji
- epics.md - z ogólną rozpiską epików do realizacji
- strategy.md - z sugerowaną strategią implementacji

3. Kolejno wygenerowałem dokładną dokumentację dla epików z podziałem na małe zadania łatwe do realizacji.

4. Od tego momentu przeszedłem do pracy z Copilotem. Wcześniej wgrałem do szkeletowych repozytoriów api i frontend `copilot-instructions.md` z Laravel Boost i bazowej instalacji Angulara

5. Przeprowadziłem ponowną analizę epików z Copilotem z zapiętymi instrukcjami frameworków do aktualizacji dokumentacji zadań, aby zmniejszyć prawdopodobieństwo błędów i korzystania ze starych danych z dokumentacji frameworków w bazie modelu językowego.

6. Następnie przeszedłem do realizowania rozpisanych zdań. Każdy epik realizowałem w osobnej sesji Copilota, żeby nie przeładować kontekstu, co jak wiadomo, wpływa na występowania błędów i halucynacji. Każdą sesję kazałem na koniec podsumować jako notatki na temat przeprowadzonych prac, na potrzeby kolejnych epików.

7. Każdą sesję Copliota zaczynałem z promptem jak:
```
    ## Role
    You are proffesional laravel 11 backend architect and developer.
    Your job is to implement planned solutions in modern laravel domain architecture.
    You work on `api` directory with laravel 11 api app.
    Application is served with `Laravel Sail` docker.

    ## Job order:
    1. Familiarize yourself with project docs from `/ai_documents`:
        1. `recruitment_task.md` - for overall context
        2. `project_brief.md` - to understand product
        3. `epics.md` - to understand analysis
        4. `strategy.md` - to get familar with suggested implementation strategy
    2. Read `ai_documents/tasks/epic-01.md` and start implementing 'EPIC1-001: Create database migration for roles table'. 
    3. Use spatie/laravel-permission
    ```

w razie potrzeby poza wspomnianymi dokumentami załączałem z `ai_documents/techs` dokument md dla danej technologii jak `laravel-permission` lub `laragent` dla dodania do kontekstu aktualnej dokumentacji. Dokumentacje pobierałem albo z `Context7` albo z oficjalnych stron technologii.

Prompt z każdą sesją był uzupełniany aby uwzględniać notatki wcześniejszych epików i dodatkowe info jak dotyczące `weatherApi`:
   
    ```
    ## Role
    You are proffesional laravel 11 backend architect and developer.
    Your job is to implement planned solutions in modern laravel domain architecture.
    You work on `/api` directory with laravel 11 api app.
    Application is served with `Laravel Sail` docker.
    Before you execute sail commands, change directory to `api` with Laravel application.
    ## Job order:
    1. Familiarize yourself with project docs from `/ai_documents`:
        1. `recruitment_task.md` - for overall context
        2. `project_brief.md` - to understand product
        3. `epics.md` - to understand analysis
        4. `strategy.md` - to get familar with suggested implementation strategy
        5. (OPTIONALY) if you find need to gather detail information from previous epics, check in `ai_documents/tasks':
            1. `2025-11-17-epic1-session.md` 
            2. `2025-11-17-epic2-session.md`
            3. `2025-11-17-epic3-session.md` 
            4. `2025-11-17-epic4-session.md`
    2. Read `ai_documents/tasks/epic-05.md`
    3. For 'EPIC5-001: Choose and configure external API integration' we will use
    ```bash
    curl -X 'GET' \
    'https://api.weatherapi.com/v1/current.json?q=San%20Diego&key=22fb337f2091430d969164411251811%20' \
    -H 'accept: application/json'
    ```

    that returns
    ```json
    { "location": { "name": "San Diego", "region": "California", "country": "United States of America", "lat": 32.7153, "lon": -117.1564, "tz_id": "America/Los_Angeles", "localtime_epoch": 1763484612, "localtime": "2025-11-18 08:50" }, "current": { "last_updated_epoch": 1763484300, "last_updated": "2025-11-18 08:45", "temp_c": 13.3, "temp_f": 55.9, "is_day": 1, "condition": { "text": "Partly cloudy", "icon": "//cdn.weatherapi.com/weather/64x64/day/116.png", "code": 1003 }, "wind_mph": 5.4, "wind_kph": 8.6, "wind_degree": 278, "wind_dir": "W", "pressure_mb": 1015, "pressure_in": 29.96, "precip_mm": 0, "precip_in": 0, "humidity": 87, "cloud": 50, "feelslike_c": 12.8, "feelslike_f": 55, "windchill_c": 12.7, "windchill_f": 54.9, "heatindex_c": 13.1, "heatindex_f": 55.5, "dewpoint_c": 11.4, "dewpoint_f": 52.6, "vis_km": 16, "vis_miles": 9, "uv": 0.2, "gust_mph": 7.2, "gust_kph": 11.6, "short_rad": 44.4, "diff_rad": 12.79, "dni": 231.41, "gti": 0 } }
    ```
    4. I've added key to env `WEATHER_API_KEY`
```

8. Do wielu funkcjonalności po stronie `api` na bieżąco były dodawane testy dla weryfikacji prawidłowego działania funkcjonalności i integracji. Dodatkowe weryfikacje wykonywane były przez `artisan tinker`.


### Pomocność LLM w kodzie, testach i dokumentacji

1. Jak wskazałem we wcześniejszej sekcji, od samego początku mocno polegałem na AI w każdym aspekcie realizacji zadania rekrutacyjnego.
2. Pierwszą wersję dokumentacji stworzyłem z claude.ai w wersji webowej po wcześniejszym samodzielnym przeanalizowania zdania i 'szkicu' strategii działania.
3. AI samodzielnie proponowało utworzenie testu do nowo utworzonego endpointa i serwisu.
4. Przez solidnie zaplanowane epiki i rozbicie na małe taski, LLM nie miał problemów z kodowaniem funkcjonalności, mając w kontekście wiedzę, co do tej pory i jak zostało zrobione. Dzięki temu udało się zachować spójność kodu i korekty wsteczne były minimalne. 

### Halucynacje i błędy

1. W przypadku halucynacji na bieżąco korygowałem w sesji chatu błędy, niekiedy wprowadzając korekty ręcznie, jednak w niewielu przypadkach. W takich sytuacjach zastosowanie fetcha do aktualnej dokumentacji, mcp sequentialthinking i doprecyzowanie rozwiązywało problem.

2. Większy problem wystąpił w zakresie Storybooka, ponieważ AI usilnie starało się dla Storybooka w v10 zaciągać zależności z wersji 8 które zostały wbudowane już w v8, co wymagało ręcznego zainstalowania dodatkowych zależności dostępnych dla v10 i wskazanie dokumentacji do fetcha, opisującej, że paczki stanowią już integralną część v10.

3. Innym przypadkiem była konieczność klarownego przedstawienia wymagań dla TicketSeedera w zakresie treści ticketów, aby dać możliwość łatwego testowania Triage Agenta. Na lipsum niestety LLM nie rozumiał jak interpretować treść zgłoszenia.