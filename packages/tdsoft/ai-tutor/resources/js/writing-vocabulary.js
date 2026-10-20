const commonWords = new Set(('about after again also another because before between both cannot could does each either ' +
    'even every from further have here into just many more most much only other over same should some such than that ' +
    'their them then there these they this those through very were what when where which while will with would your').split(' '));

/** Frequency hints, not a language-quality score or a claim that repetition is wrong. */
export function repeatedWritingWords(original) {
    const counts = new Map();
    for (const token of String(original ?? '').match(/[\p{L}\p{M}]+(?:['’\-][\p{L}\p{M}]+)*/gu) ?? []) {
        const word = token.toLowerCase();
        if (!/^[a-z]{4,}$/.test(word) || commonWords.has(word)) continue;
        counts.set(word, (counts.get(word) ?? 0) + 1);
    }
    return [...counts].filter(([, count]) => count >= 3)
        .sort((a, b) => b[1] - a[1] || a[0].localeCompare(b[0], 'en')).slice(0, 5)
        .map(([word, count]) => ({ word, count }));
}
