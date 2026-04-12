# Woo Book Importer — Changelog

## 1.8.2 — 2025-06-15
### Fixed
- **Critical Category Bug:** Fixed a long-standing issue where the plugin could create blank, invalid, or numeric product categories in certain cases. The category assignment logic now uses a more robust and stable method for finding and creating terms, preventing data corruption while retaining full support for hierarchical categories.

## 1.8.1 — 2025-06-15
### Fixed
- **Category Assignment:** Merged logic from a previously working version with new robust checks to permanently fix the bug that created numeric categories. The importer now correctly handles all genre formats from the API.

## 1.8.0 — 2025-06-15
### Fixed
- **"Today" Stat Counter:** Fixed the date calculation to correctly count all books imported on the current day.
- **Dashboard UI:** The heading for the "Recently Imported Books" table no longer disappears after an import.
- The UI now correctly updates both stats and the recent books table after an import.

## 1.7.9 — 2025-06-15
### Fixed
- Reverted category assignment logic to the previous stable version to resolve a fatal error that was causing the import process to freeze.
- Established a stable baseline for UI and stat counter fixes.

## 1.5.0 — 2025-06-13
### Added
- Created a new unified "Book Importer Dashboard".
- Added a management table to view all imported books.
- Added placeholders for fetching Keepa data and updating product price/stock.
- Added a new `keepa-api-handler.php` file for future integration.
### Changed
- Refactored the admin UI to merge the importer and the dashboard into a single management page.
- Reorganized the admin menu structure.

## 1.4.4 — 2025-06-13
### Added
- Added the basic structure for the "Keepa Dashboard".
- The new dashboard page now lists all previously imported books with placeholders for future Keepa data.

## 1.4.3 — 2025-06-13
### Added
- Added a new settings page for Keepa API integration.
- Laid the groundwork for premium features.

## 1.4.2 — 2025-06-12
### Fixed
- Improved the image handler to more aggressively search for the highest-resolution cover photos.

## 1.4.1 — 2025-06-12
### Fixed
- Rewrote the category assignment logic to use a more robust helper function to prevent the "ghost category" bug.

## 1.3.0 — 2025-06-12
### Changed
- Rebuilt the plugin's user interface to use AJAX.
- Added a progress bar for bulk imports.
### Fixed
- Stabilized the product creation process, fixing several random server errors.

## 1.2.0 — 2025-06-12
### Added
- Added a robust image downloading function with a fallback to the Open Library API.
- Implemented a custom `/uploads/book-covers/` directory.

## 1.1.0 — 2025-06-12
### Added
- Feature to import a book's dimensions.
### Fixed
- Addressed multiple fatal errors during the import process.

## 1.0.0 — 2025-06-12
### Added
- Initial release.
- Core feature to import a book from a single ISBN.
