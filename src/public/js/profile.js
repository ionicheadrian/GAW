document.addEventListener('DOMContentLoaded', function () {
    const stats = document.querySelectorAll('.stat h4');
    stats.forEach((stat, index) => {
        const finalValue = parseInt(stat.textContent);
        let currentValue = 0;
        const increment = Math.ceil(finalValue / 30);

        const counter = setInterval(() => {
            currentValue += increment;
            if (currentValue >= finalValue) {
                currentValue = finalValue;
                clearInterval(counter);
            }
            stat.textContent = stat.textContent.includes('kg') ?
                currentValue + 'kg' : currentValue;
        }, 50);
    });
});