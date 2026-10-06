import test from 'node:test';
import assert from 'node:assert/strict';
import { repeatedWritingWords } from '../../resources/js/writing-vocabulary.js';
import { writingReport } from '../../resources/js/writing-report.js';

test('counts exact word forms without matching substrings or silently merging variants', () => {
    assert.deepEqual(repeatedWritingWords('Learning learning LEARNING. Learn learns learned. Online online online. online-learning online-learning online-learning.'),
        [{ word: 'learning', count: 3 }, { word: 'online', count: 3 }]);
});
test('ignores function words, short words and non-English tokens; retains only five frequent words', () => {
    assert.deepEqual(repeatedWritingWords('This this this have have have. AI AI AI. học học học. learning learning.'), []);
    const text = ['zebra', 'books', 'games', 'school', 'online', 'students'].map(word => `${word} ${word} ${word}`).join(' ');
    assert.equal(repeatedWritingWords(text).length, 5);
    assert.deepEqual(repeatedWritingWords(null), []);
});
test('report frequency follows assessed text rather than revised draft', () => {
    const report = writingReport({ id: '1', revision: 1, status: 'completed', original: 'Online online ONLINE', result: { criteria: {} } },
        { content: 'Books books books books' });
    assert.ok(report.text.includes('online: 3 lần'));
    assert.ok(!report.text.includes('books:'));
});
