/**
 * Christmas Snow Effect
 * Creates beautiful falling snowflakes across the screen
 */

(function() {
    'use strict';
    
    // Configuration
    const config = {
        snowflakeCount: 50,        // Number of snowflakes
        minSize: 5,                // Minimum snowflake size (px)
        maxSize: 15,               // Maximum snowflake size (px)
        minSpeed: 1,               // Minimum fall speed
        maxSpeed: 3,               // Maximum fall speed
        minOpacity: 0.3,           // Minimum opacity
        maxOpacity: 0.9,           // Maximum opacity
        windStrength: 0.5,         // Wind effect strength
        refreshRate: 50            // Animation refresh rate (ms)
    };
    
    // Create snow container
    let snowContainer = document.getElementById('snow-container');
    if (!snowContainer) {
        snowContainer = document.createElement('div');
        snowContainer.id = 'snow-container';
        snowContainer.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 9999;
            overflow: hidden;
        `;
        document.body.appendChild(snowContainer);
    }
    
    // Snowflake class
    class Snowflake {
        constructor() {
            this.reset();
            this.x = Math.random() * window.innerWidth;
            this.y = -this.size;
        }
        
        reset() {
            this.size = Math.random() * (config.maxSize - config.minSize) + config.minSize;
            this.speed = Math.random() * (config.maxSpeed - config.minSpeed) + config.minSpeed;
            this.opacity = Math.random() * (config.maxOpacity - config.minOpacity) + config.minOpacity;
            this.x = Math.random() * window.innerWidth;
            this.y = -this.size;
            this.wind = (Math.random() - 0.5) * config.windStrength;
            this.swing = Math.random() * Math.PI * 2;
            this.swingSpeed = Math.random() * 0.02 + 0.01;
        }
        
        update() {
            // Move snowflake down
            this.y += this.speed;
            
            // Add wind effect with swinging motion
            this.swing += this.swingSpeed;
            this.x += this.wind + Math.sin(this.swing) * 0.5;
            
            // Reset if off screen
            if (this.y > window.innerHeight + this.size) {
                this.reset();
            }
            
            // Reset if off screen horizontally
            if (this.x < -this.size) {
                this.x = window.innerWidth + this.size;
            } else if (this.x > window.innerWidth + this.size) {
                this.x = -this.size;
            }
        }
        
        draw(ctx) {
            ctx.beginPath();
            ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
            ctx.fillStyle = `rgba(255, 255, 255, ${this.opacity})`;
            ctx.fill();
            
            // Add sparkle effect occasionally
            if (Math.random() > 0.95) {
                ctx.beginPath();
                ctx.arc(this.x, this.y, this.size * 0.5, 0, Math.PI * 2);
                ctx.fillStyle = `rgba(255, 255, 255, ${this.opacity * 1.5})`;
                ctx.fill();
            }
        }
    }
    
    // Create canvas for snow
    let canvas = document.getElementById('snow-canvas');
    if (!canvas) {
        canvas = document.createElement('canvas');
        canvas.id = 'snow-canvas';
        canvas.style.cssText = `
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
        `;
        snowContainer.appendChild(canvas);
    }
    
    const ctx = canvas.getContext('2d');
    let snowflakes = [];
    let animationId;
    
    // Initialize canvas size
    function resizeCanvas() {
        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;
    }
    
    // Initialize snowflakes
    function initSnowflakes() {
        snowflakes = [];
        for (let i = 0; i < config.snowflakeCount; i++) {
            const flake = new Snowflake();
            flake.y = Math.random() * window.innerHeight;
            snowflakes.push(flake);
        }
    }
    
    // Animation loop
    function animate() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        
        snowflakes.forEach(flake => {
            flake.update();
            flake.draw(ctx);
        });
        
        animationId = requestAnimationFrame(animate);
    }
    
    // Start animation
    function startSnow() {
        resizeCanvas();
        initSnowflakes();
        
        if (animationId) {
            cancelAnimationFrame(animationId);
        }
        
        animate();
    }
    
    // Handle window resize
    let resizeTimeout;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(() => {
            resizeCanvas();
            // Reposition snowflakes that are off-screen
            snowflakes.forEach(flake => {
                if (flake.x > window.innerWidth) {
                    flake.x = window.innerWidth - flake.size;
                }
                if (flake.y > window.innerHeight) {
                    flake.y = window.innerHeight - flake.size;
                }
            });
        }, 100);
    });
    
    // Check if we should show snow
    // By default, always show snow. Can be controlled via localStorage or manual toggle
    function shouldShowSnow() {
        // Check localStorage for user preference
        const snowPreference = localStorage.getItem('christmasSnow');
        if (snowPreference !== null) {
            return snowPreference === 'true';
        }
        // Default: always show snow
        return true;
    }
    
    // Initialize on page load
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            if (shouldShowSnow()) {
                startSnow();
            }
        });
    } else {
        if (shouldShowSnow()) {
            startSnow();
        }
    }
    
    // Export function to manually toggle snow (for settings if needed)
    window.toggleChristmasSnow = function(enable) {
        localStorage.setItem('christmasSnow', enable ? 'true' : 'false');
        
        if (enable && !animationId) {
            startSnow();
            snowContainer.style.display = 'block';
        } else if (!enable && animationId) {
            cancelAnimationFrame(animationId);
            animationId = null;
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            snowContainer.style.display = 'none';
        }
    };
})();

