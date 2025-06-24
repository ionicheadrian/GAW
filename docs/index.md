# EcoManager – Overall Architecture and Development Phases

---

## Table of Contents  
1. [Context and Scope](#context-and-scope)  
2. [Development Phases](#development-phases)  
3. [Architectural Decisions](#architectural-decisions)
4.   [C4 Architecture](architecture.md#level-1-context)
5. [API Reference](api.md#authentication)
6. [Installation & Running](install.md#prerequisites)
---

## Context and Scope

**EcoManager** is a web application that supports citizens, collection staff and administrators in reporting, sorting and recycling urban waste (household, paper, plastic, etc.). It also provides:

- **User reports** of overflow or litter hotspots  
- **Interactive map** (Leaflet + OpenStreetMap)  
- **Statistics & charts** per day/week/month, exportable to HTML, CSV and PDF  
- **Admin module** for managing users, categories and locations  
- **(Bonus)** a simple recycling-process simulator  

### Main Objectives  
- Rapid reporting & visualization of waste data  
- Export in CSV, PDF and JSON  
- Responsive, modern interface (HTML5 + CSS3, no frameworks)  
- Secure (SQL Injection & XSS prevention)  
- Fully “vanilla” PHP + JS (no frameworks)  

---

## Development Phases

| Phase                     | Duration       | Objectives                                     |
|---------------------------|----------------|------------------------------------------------|
| **1. Foundation**         | 1–2 weeks      | • Define architecture<br>• Setup Git <br>• Project structure |
| **2. Backend Core**       | 2-3 weeks      | • Models & DB (SQLite)<br>• Services & REST API<br>• OSM integration   |
| **3. Frontend**           | 2-3 weeks      | • Responsive HTML/CSS<br>• Dynamic JS (Ajax, Leaflet, Chart.js)       |
| **4. Admin & Export**     | 1–2 weeks      | • Admin module<br>• CSV/PDF/JSON export<br>• Recycling simulator     |
| **5. Testing & Security** | 1 week         | • Input validation & XSS/SQLi protection<br>• UAT & bug-fix            |
| **6. Deployment**         | 2-3 days       | • Simple production deploy<br>• Final documentation             |

---

## Architectural Decisions

### Technology Choices

| Component       | Technology                 | Why?                                         |
|-----------------|----------------------------|----------------------------------------------|
| Back-end        | PHP 8.2 & Apache & SQLite  | Simple, no separate DB server                |
| Front-end       | Vanilla JS & Leaflet & Chart.js | No JS framework, full control           |
| Map Tiles       | OpenStreetMap              | Free, open data                              |
| Exports         | PHP + JS helpers           | CSV nativ, PDF via TCPDF, JSON direct        |

### Patterns & Practices

- **Layered**: Controllers → Services → Repositories → DB  
- **C4** for architecture docs  
- **Secure by Design**: PDO prepared statements, `htmlspecialchars()`, session-based auth  

---

## Key Highlights

### Strengths
- **Modularity**  
  Code organized in layers (Controllers, Services, Repositories, Templates), each with a clear responsibility.  
- **Maintainability**  
  File structure and C4 documentation that facilitate onboarding and ongoing maintenance.  
- **Performance**  
  Dynamic interfaces using AJAX, Leaflet, and Chart.js; minimal static pages; SQLite for fast data access.  
- **Security**  
  - Session-based authentication with `password_hash()`  
  - PDO Prepared Statements (SQL Injection prevention)  
  - `htmlspecialchars()` in templates (XSS prevention)  

### Modern Technologies
- **REST API**  
  JSON endpoints clearly separated from the UI.  
- **Responsive Web Design**  
  Validated HTML5 + CSS3, adaptive layout for mobile & desktop.  
- **Interactive Map**  
  Leaflet + OpenStreetMap for waste reporting.  
- **Data Visualization**  
  Chart.js for daily/weekly/monthly charts.  
- **Export Formats**  
  CSV, PDF, JSON via dedicated endpoints.  

### Best Practices
- **Separation of Concerns**  
  UI (templates + JS) vs. business logic (Controllers/Services) vs. persistence (Repositories).  
- **DRY (Don’t Repeat Yourself)**  
  Reusable functions and services, PSR-0 autoloading.  
- **Error Handling**  
  PDO `ERRMODE_EXCEPTION` and appropriate HTTP status codes (400/401/403/500).  
- **Input Validation & Sanitization**  
  - Client-side (JS) and server-side (Validators) validation  
  - Filtering and sanitization before insertion  
- **Version Control & Documentation**  
  Git + modular Markdown documentation (C4, API, installation)  

