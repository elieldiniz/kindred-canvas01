window.kcCarousel = function kcCarousel(total) {
    return {
        active: 0,
        total: total,
        autoTimer: null,

        init() {
            this.startAuto();

            // Keyboard navigation
            window.addEventListener('keydown', (e) => {
                if (e.key === 'ArrowLeft')  this.prev();
                if (e.key === 'ArrowRight') this.next();
            });
        },

        getPos(index) {
            let diff = index - this.active;
            // Wrap around for circular behaviour
            if (diff >  Math.floor(this.total / 2)) diff -= this.total;
            if (diff < -Math.floor(this.total / 2)) diff += this.total;

            if (diff === 0)  return '0';
            if (diff === -1) return '-1';
            if (diff === 1)  return '1';
            if (diff === -2) return '-2';
            if (diff === 2)  return '2';
            return 'hidden';
        },

        goTo(index) {
            this.active = index;
            this.resetAuto();
        },

        next() {
            this.active = (this.active + 1) % this.total;
            this.resetAuto();
        },

        prev() {
            this.active = (this.active - 1 + this.total) % this.total;
            this.resetAuto();
        },

        navigate(index) {
            if (index !== this.active) this.goTo(index);
        },

        startAuto() {
            this.autoTimer = setInterval(() => this.next(), 4500);
        },

        resetAuto() {
            clearInterval(this.autoTimer);
            this.startAuto();
        },
    };
}
