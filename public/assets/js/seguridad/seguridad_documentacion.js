(function(){
    var c=document.getElementById('bubbles');
    if(!c)return;
    for(var i=0;i<15;i++){
        var b=document.createElement('div');b.className='bubble';
        var s=Math.random()*50+20;b.style.width=s+'px';b.style.height=s+'px';
        b.style.left=Math.random()*100+'vw';
        b.style.animationDuration=(Math.random()*5+6)+'s';
        b.style.animationDelay=(Math.random()*6)+'s';
        c.appendChild(b);
    }
})();
