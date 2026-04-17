# Woo Book Importer - Changelog


## 1.8.2 - 2025-06-15
### Fixed
- **Critical Category Bug:** Fixed a long-standing issue where the plugin could create blank, invalid, or numeric product categories in certain cases. The category assignment logic now uses a more robust and stable method for finding and creating terms, preventing data corruption while retaining full support for hierarchical categories.

## 1.8.1_gemini - 2025-06-15
### Fixed
- **Category Assignment:** Merged logic from a previously working version with new robust checks to permanently fix the bug that created numeric categories. The importer now correctly handles all genre formats from the API.

## 1.8.0_gemini - 2025-06-15
### Fixed
- **"Today" Stat Counter:** Fixed the date calculation to correctly count all books imported on the current day.
- **Dashboard UI:** The heading for the "Recently Imported Books" table no longer disappears after an import.
- The UI now correctly updates both stats and the recent books table after an import.

## 1.7.9_gemini - 2025-06-15
### Fixed
- Reverted category assignment logic to the previous stable version to resolve a fatal error that was causing the import process to freeze.
- Established a stable baseline for UI and stat counter fixes.

... (rest of changelog)