export class StudyClock {
    constructor(now) { this.previous = now; this.lastInteraction = now; this.elapsed = 0; }
    interact(now) { this.lastInteraction = now; }
    step(now, visible, focused, mediaPlaying = false) {
        const delta = Math.max(0, Math.min(5000, now - this.previous));
        this.previous = now;
        if (visible && focused && (mediaPlaying || now - this.lastInteraction <= 120000)) this.elapsed += delta;
        return Math.floor(this.elapsed / 1000);
    }
}
