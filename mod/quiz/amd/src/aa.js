define([], function () {

    return {
        init: function() {

            const circle = document.getElementById('quiz-timer-circle');
            const text = document.getElementById('quiz-timer-text');
            const defaultTimer = document.getElementById('quiz-time-left');

            const r = circle.getAttribute('r');
            const circumference = 2 * Math.PI * r;

            circle.style.strokeDasharray = circumference;

            function updateCircle() {
                const raw = defaultTimer.innerText.trim(); // example: "12:45"
                if (!raw || raw === '00:00') return;

                const parts = raw.split(':');
                const seconds = (+parts[0]) * 60 + (+parts[1]);

                text.innerText = raw; // Update your UI

                const percentage = (seconds / window.quizTotalTime) * 100;
                const offset = circumference - (percentage / 100) * circumference;

                circle.style.strokeDashoffset = offset;
                requestAnimationFrame(updateCircle);
            }

            requestAnimationFrame(updateCircle);
        }
    };
});
