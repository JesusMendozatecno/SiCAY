        for(let i=0; i<15; i++){
            let b = document.createElement('div'); b.className = 'bubble';
            let size = Math.random()*50 + 20 + 'px';
            b.style.width = size; b.style.height = size;
            b.style.left = Math.random()*100 + 'vw';
            b.style.animationDuration = Math.random()*5 + 5 + 's';
            document.getElementById('bubbles').appendChild(b);
        }