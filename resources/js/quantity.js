// Mirrors App\Support\Quantity::format so portion scaling in the browser
// shows the same fractions (½, ⅓, ¼ …) as the server.
const MARKS = [
    [0, ''],
    [0.25, '¼'],
    [1 / 3, '⅓'],
    [0.5, '½'],
    [2 / 3, '⅔'],
    [0.75, '¾'],
    [1, ''],
];

export function formatQty(value) {
    if (value === null || value === undefined || Number.isNaN(value)) return '';

    let whole = Math.floor(value + 1e-9);
    const fraction = value - whole;

    let best = MARKS[0];
    let gap = 1;
    for (const mark of MARKS) {
        const distance = Math.abs(fraction - mark[0]);
        if (distance < gap) {
            gap = distance;
            best = mark;
        }
    }

    if (gap > 0.04) return String(Math.round(value * 10) / 10).replace('.', ',');

    if (best[0] === 1) whole += 1;
    const glyph = best[0] === 1 ? '' : best[1];

    if (whole === 0) return glyph || '0';
    return glyph ? `${whole} ${glyph}` : String(whole);
}
