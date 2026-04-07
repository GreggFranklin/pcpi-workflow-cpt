Here’s a clean, production-ready **README.md** you can drop into your repo:

---

# PCPI Workflow CPT

Custom Post Type (CPT) plugin for managing **PCPI workflows** in WordPress.

This plugin provides a structured, scalable way to define and manage workflows used throughout the PCPI ecosystem, including integration points with Gravity Forms, dashboards, and workflow-driven automation.

---

## Overview

**PCPI Workflow CPT** introduces a dedicated post type for workflows, allowing administrators and developers to:

* Define workflows in a centralized location
* Manage workflow metadata via WordPress UI
* Standardize workflow configuration across plugins
* Enable registry-driven architecture instead of hardcoded arrays

This is a foundational plugin designed to support the broader **PCPI Workflow Engine** and related tools.

---

## Features

* Custom Post Type: `pcpi_workflow`
* Admin UI for managing workflows
* Support for custom fields / metadata
* Designed for integration with:

  * Gravity Forms
  * PCPI Workflow Engine
  * Staff Dashboard
  * PDF generation workflows
* Extensible and developer-friendly

---

## Use Case

Instead of defining workflows in code like this:

```php
$workflows = [
    'oakland' => [...],
    'vast'    => [...],
];
```

You can manage them via WordPress:

* Create a Workflow in the admin
* Store configuration as meta
* Dynamically resolve workflows at runtime

---

## Installation

### Option 1: Upload via WordPress

1. Download the plugin ZIP
2. Go to **Plugins → Add New**
3. Click **Upload Plugin**
4. Upload the ZIP file
5. Activate the plugin

### Option 2: Manual Install

1. Clone the repository:

```bash
git clone https://github.com/GreggFranklin/pcpi-workflow-cpt.git
```

2. Move to your plugins directory:

```
/wp-content/plugins/pcpi-workflow-cpt
```

3. Activate in WordPress admin

---

## Post Type Details

| Property  | Value                              |
| --------- | ---------------------------------- |
| Post Type | `pcpi_workflow`                    |
| Public    | false                              |
| UI        | true                               |
| Supports  | Title (and optional custom fields) |

---

## Data Structure

Each workflow can store metadata such as:

* `applicant_form_id`
* `questionnaire_form_id`
* `review_form_id`
* `pdf_template_id`
* `workflow_key`
* `labels`
* `feature_flags`
* `field_maps`

This allows workflows to act as a **single source of truth**.

---

## Integration Example

Retrieve workflows programmatically:

```php
$workflows = get_posts([
    'post_type' => 'pcpi_workflow',
    'numberposts' => -1,
]);

foreach ( $workflows as $workflow ) {
    $config = get_post_meta( $workflow->ID );
}
```

---

## Recommended Architecture

This plugin is best used as part of a registry-driven system:

```
Workflow CPT (source of truth)
        ↓
Workflow Engine (resolver)
        ↓
Forms / Dashboard / PDFs
```

---

## Roadmap

* Admin UI for structured workflow configuration
* JSON import/export
* Validation layer (field maps, form IDs, etc.)
* Workflow versioning
* REST API endpoints
* UI enhancements for non-technical users

---

## Development Notes

* Built following WordPress coding standards
* Designed for PHP 8.1+ compatibility
* Avoids hard dependencies on other plugins
* Intended to be lightweight and extensible

---

## Contributing

1. Fork the repository
2. Create a feature branch
3. Submit a pull request

---

## License

GPLv2 or later

---

## Author

**Gregg Franklin**
[https://github.com/GreggFranklin](https://github.com/GreggFranklin)

---

## Related Projects

* PCPI Workflow Engine
* PCPI Staff Dashboard
* PCPI Gravity Tools
* PCPI PDF Templates

---
