// Inject random spacers into the projects grid to create dynamic whitespace
document.addEventListener('DOMContentLoaded', function() {
    const grid = document.querySelector('.projects-grid');
    if (!grid) return;

    const cards = Array.from(grid.querySelectorAll('.project-card'));
    if (cards.length <= 2) return;

    const cols = window.innerWidth >= 1100 ? 4 : (window.innerWidth >= 900 ? 3 : 2);
    // Compute spacer count relative to columns so mobile gets fewer spacers
    let maxSpacers;
    if (cols === 4) maxSpacers = Math.max(1, Math.floor(cards.length / 3));
    else if (cols === 3) maxSpacers = Math.max(1, Math.floor(cards.length / 4));
    else /* cols === 2 */ maxSpacers = Math.max(1, Math.floor(cards.length / 6));
    const placedRows = new Set();

    function insertSpacerAt(index) {
        const spacer = document.createElement('div');
        spacer.className = 'projects-grid-spacer';
        const child = grid.children[index];
        if (child) grid.insertBefore(spacer, child);
        else grid.appendChild(spacer);
    }

    // Insert a spacer that spans the entire row (creates a full empty row)
    function insertRowSpacerAt(row) {
        const spacer = document.createElement('div');
        spacer.className = 'projects-grid-row-spacer';
        const colsNow = cols;
        const rowStart = row * colsNow;
        const child = grid.children[rowStart];
        if (child) grid.insertBefore(spacer, child);
        else grid.appendChild(spacer);
    }

    // Try to insert up to maxSpacers, one per row at most
    let attempts = 0;
    let placed = 0;
    // allow more attempts for larger grids
    const attemptLimit = Math.max(40, cards.length * 8);
    while (placed < maxSpacers && attempts < attemptLimit) {
        attempts++;
        // number of current slots (cards + already inserted spacers)
        const slots = grid.children.length;
        const idx = Math.floor(Math.random() * slots);
        const row = Math.floor(idx / cols);
        if (placedRows.has(row)) continue;
        // Occasionally insert a full-row spacer instead of a single-cell spacer
        // Only insert row-spacers between rows (not above first row or below last row)
        const slotsNow = grid.children.length;
        const rowsNow = Math.ceil(slotsNow / cols);
        const rowSpacerChance = cols >= 4 ? 0.35 : (cols === 3 ? 0.2 : 0.05);
        if (Math.random() < rowSpacerChance) {
            // ensure this is between rows (not before first row and not after last row)
            if (row > 0 && row < rowsNow) {
                insertRowSpacerAt(row);
                placedRows.add(row);
                placed++;
                continue;
            }
        }
        // avoid placing spacer at very start or end
        if (idx === 0 || idx >= slots) continue;
        insertSpacerAt(idx);
        placedRows.add(row);
        placed++;
    }

    // Ensure layout updates on resize (re-run once) — only when column count changes
    let resized = false;
    window.addEventListener('resize', function() {
        if (resized) return; // do not continuously re-run
        const newCols = window.innerWidth >= 1100 ? 4 : (window.innerWidth >= 900 ? 3 : 2);
        // If columns didn't change, skip reload (prevents mobile address-bar hide/show reloads)
        if (newCols === cols) return;
        resized = true;
        setTimeout(() => location.reload(), 250);
    });
});
