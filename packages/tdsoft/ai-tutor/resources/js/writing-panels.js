/** Ephemeral UI preferences only; never stores essay text or assessment content. */
export class WritingPanelState {
    constructor(limit = 30) { this.limit = limit; this.entries = new Map(); }
    remember(id, states) {
        if (!id) return;
        this.entries.delete(id);
        this.entries.set(id, { ...states });
        if (this.entries.size > this.limit) this.entries.delete(this.entries.keys().next().value);
    }
    isOpen(id, key) { return this.entries.get(id)?.[key] === true; }
}
