<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>TVMaze</title>
<style>
body { margin:0; font-family:'Arial',sans-serif; background-color:#160940; color:white; }
header { padding:20px; background-color:#160940; position: sticky; top:0; z-index:1000; box-shadow:0 2px 5px rgba(0,0,0,0.5);}
h1 { margin:0 0 10px 0; text-align:center; color:#FF9900; }
.search-container { display:flex; justify-content:center; margin-bottom:20px; gap:10px; flex-wrap:wrap; position:relative; }
.autocomplete-wrapper { position: relative; display:inline-block; }
input[type="text"], select { padding:10px; font-size:16px; border-radius:5px; border:none; width:200px; }
button { padding:10px 20px; font-size:16px; border:none; border-radius:5px; background-color:#FF9900; color:white; cursor:pointer; }
.autocomplete-list { position:absolute; top:100%; left:0; width:100%; background-color:#160940; border:1px solid #FF9900; max-height:200px; overflow-y:auto; z-index:10; border-radius:0 0 5px 5px; }
.autocomplete-list div { padding:10px; cursor:pointer; }
.autocomplete-list div:hover { background-color:#FF9900; color:#160940; }
.category-section { margin-top:30px; padding:0 20px; position:relative; }
.category-title { font-size:20px; font-weight:bold; color:#FF9900; margin-bottom:10px; }
.horizontal-scroll-wrapper { position:relative; }
.horizontal-scroll { display:flex; overflow-x:auto; gap:20px; padding:10px 0; scroll-behavior: smooth; }
.horizontal-scroll::-webkit-scrollbar { height:8px; }
.horizontal-scroll::-webkit-scrollbar-thumb { background-color:#FF9900; border-radius:4px; }
.cards-container { display:grid; grid-template-columns:repeat(auto-fill,minmax(180px,1fr)); gap:20px; padding:10px 0; }
.card { background-color:#1a0a70; border-radius:10px; overflow:hidden; box-shadow:0 0 10px #000; text-align:center; cursor:pointer; transition:transform 0.2s; width:180px; flex:0 0 auto; }
.card:hover { transform:scale(1.05); }
.card img { width:100%; height:270px; object-fit:cover; }
.card h3 { margin:10px 0 5px 0; font-size:16px; }
.card p { margin:3px 0; font-size:14px; }
.genre-badge { display:inline-block; padding:5px 8px; border-radius:5px; color:white; margin:2px; font-size:12px; border:1px solid #FF9900; }
.rating { margin:3px 0; font-size:14px; color:#FF9900; font-weight:bold; }
.back-btn { background-color:#FF9900; border:none; padding:10px 20px; color:white; border-radius:5px; cursor:pointer; margin-bottom:20px; }
.container { display:flex; flex-wrap:wrap; justify-content:center; padding:20px; gap:20px; }
.poster { flex:1 1 300px; max-width:300px; }
.poster img { width:100%; border-radius:10px; }
.details { flex:2 1 400px; }
.details p { margin:5px 0; }
.summary { margin-top:15px; line-height:1.4; }
.summary a { color:#FF9900; }
.episode-list { margin-top:15px; max-height:500px; overflow-y:auto; }
.season-title { font-weight:bold; font-size:16px; margin-top:15px; color:#FF9900; }
.episode-header, .episode-item { display:grid; grid-template-columns:50px 1fr 80px; padding:5px 0; align-items:center; }
.episode-header { font-weight:bold; color:#FF9900; margin-bottom:5px; }
.episode-item { cursor:pointer; transition:0.2s; }
.episode-item:hover { background-color:#FF9900; color:#160940; }
/* panah kiri-kanan */
.scroll-button { position:absolute; top:50%; transform:translateY(-50%); background-color:#FF9900; border:none; color:white; font-size:20px; border-radius:50%; width:35px; height:35px; cursor:pointer; display:flex; align-items:center; justify-content:center; z-index:2; }
.scroll-button-left { left:0; }
.scroll-button-right { right:0; }
</style>
</head>
<body>
<header>
<h1>TVMaze</h1>
<div id="searchSection" class="search-container">
  <div class="autocomplete-wrapper">
    <input type="text" id="searchInput" placeholder="Search shows..." oninput="autocompleteShows()">
    <div id="autocomplete-list" class="autocomplete-list"></div>
  </div>
  <select id="filterLanguage"><option value="">All Languages</option></select>
  <select id="filterGenre"><option value="">All Genres</option></select>
  <button onclick="searchShows()">Search</button>
</div>
</header>

<div id="mainContent"></div>
<div id="detailContainer"></div>

<script>
const mainContent=document.getElementById('mainContent');
const autocompleteList=document.getElementById('autocomplete-list');
const searchInput=document.getElementById('searchInput');
const filterLanguage=document.getElementById('filterLanguage');
const filterGenre=document.getElementById('filterGenre');
const searchSection=document.getElementById('searchSection');
const detailContainer=document.getElementById('detailContainer');

let popularShows=[];
let allLanguages=new Set();
let genreColors={};

function hashStringToHue(str){
    let hash=0;
    for(let i=0;i<str.length;i++){hash=str.charCodeAt(i)+(hash<<5)-hash;}
    return Math.abs(hash)%360;
}

function getUniqueColor(genre){
    if(genreColors[genre]) return genreColors[genre];
    const hue = hashStringToHue(genre);
    const saturation = 70;
    const lightness = 35;
    const color = `hsl(${hue}, ${saturation}%, ${lightness}%)`;
    genreColors[genre]=color;
    return color;
}

async function loadPopularShows(){
    try{
        const pages = Array.from({length:50}, (_, i) => i);
        let allShows=[];
        await Promise.all(pages.map(async p=>{
            const res=await fetch(`https://api.tvmaze.com/shows?page=${p}`);
            const data=await res.json();
            data.forEach(s => { if(s.language) allLanguages.add(s.language); });
            allShows=[...allShows,...data];
        }));
        popularShows=allShows;
        populateFilters(allShows);
        displayHomePage();
    }catch(err){console.error(err);}
}

function populateFilters(data){
    filterLanguage.innerHTML='<option value="">All Languages</option>';
    [...allLanguages].sort().forEach(lang=>{
        const opt=document.createElement('option'); opt.value=lang; opt.textContent=lang; filterLanguage.appendChild(opt);
    });
    const genres=new Set();
    data.forEach(s=>{ if(s.genres) s.genres.forEach(g=>genres.add(g)); });
    filterGenre.innerHTML='<option value="">All Genres</option>';
    [...genres].sort().forEach(g=>{
        const opt=document.createElement('option'); opt.value=g; opt.textContent=g; filterGenre.appendChild(opt);
        getUniqueColor(g);
    });
}

function createHorizontalCardsSection(title, shows){
    const section=document.createElement('div'); section.className='category-section';
    section.innerHTML=`<div class="category-title">${title}</div>`;
    const wrapper=document.createElement('div'); wrapper.className='horizontal-scroll-wrapper';
    const container=document.createElement('div'); container.className='horizontal-scroll';
    shows.forEach(show=>{
        const card=document.createElement('div'); card.className='card';
        card.style.width='180px';
        card.onclick=()=>{loadDetail(show.id);};
        card.innerHTML=`
            <img src="${show.image?show.image.medium:'https://via.placeholder.com/210x295?text=No+Image'}" alt="${show.name}">
            <h3>${show.name}</h3>
            <p>${show.language||'-'}</p>
            <div>${(show.genres||[]).map(g=>`<span class="genre-badge" style="background-color:${genreColors[g]}">${g}</span>`).join('')}</div>
            <p class="rating">Rating: ${show.rating && show.rating.average?show.rating.average:'-'}</p>
        `;
        container.appendChild(card);
    });
    wrapper.appendChild(container);

    const btnLeft=document.createElement('button'); btnLeft.className='scroll-button scroll-button-left'; btnLeft.innerHTML='&#8592;';
    const btnRight=document.createElement('button'); btnRight.className='scroll-button scroll-button-right'; btnRight.innerHTML='&#8594;';
    btnLeft.onclick=()=>{container.scrollBy({left:-200, behavior:'smooth'});};
    btnRight.onclick=()=>{container.scrollBy({left:200, behavior:'smooth'});};
    wrapper.appendChild(btnLeft); wrapper.appendChild(btnRight);

    section.appendChild(wrapper);
    return section;
}

function createGridCardsSection(title, shows){
    const section=document.createElement('div'); section.className='category-section';
    section.innerHTML=`<div class="category-title">${title}</div>`;
    const container=document.createElement('div'); container.className='cards-container';
    shows.forEach(show=>{
        const card=document.createElement('div'); card.className='card';
        card.onclick=()=>{loadDetail(show.id);};
        card.innerHTML=`
            <img src="${show.image?show.image.medium:'https://via.placeholder.com/210x295?text=No+Image'}" alt="${show.name}">
            <h3>${show.name}</h3>
            <p>${show.language||'-'}</p>
            <div>${(show.genres||[]).map(g=>`<span class="genre-badge" style="background-color:${genreColors[g]}">${g}</span>`).join('')}</div>
            <p class="rating">Rating: ${show.rating && show.rating.average?show.rating.average:'-'}</p>
        `;
        container.appendChild(card);
    });
    section.appendChild(container);
    return section;
}

function displayHomePage(){
    detailContainer.innerHTML='';
    mainContent.style.display='block';
    mainContent.innerHTML='';
    const popular=popularShows.slice().sort((a,b)=>(b.rating.average||0)-(a.rating.average||0)).slice(0,12);
    mainContent.appendChild(createHorizontalCardsSection('Popular Picks', popular));
    const topGenres=['Action','Romance','Comedy','Horror','Adventure','Thriller'];
    topGenres.forEach(genre=>{
        const topGenreShows=popularShows.filter(s=>s.genres.includes(genre))
                                        .slice().sort((a,b)=>(b.rating.average||0)-(a.rating.average||0))
                                        .slice(0,12);
        if(topGenreShows.length>0) mainContent.appendChild(createHorizontalCardsSection(`Top ${genre}`, topGenreShows));
    });
    mainContent.appendChild(createGridCardsSection('All Shows', popularShows.slice(0,150)));
}

// AUTOCOMPLETE
function autocompleteShows(){
    const query=searchInput.value.toLowerCase();
    autocompleteList.innerHTML='';
    if(!query) return;
    const suggestions=popularShows.filter(s=>s.name.toLowerCase().includes(query)).slice(0,5);
    suggestions.forEach(s=>{
        const div=document.createElement('div');
        div.textContent=s.name;
        div.onclick=()=>{ loadDetail(s.id); };
        autocompleteList.appendChild(div);
    });
}

document.addEventListener('click',function(e){if(!autocompleteList.contains(e.target) && e.target!==searchInput){autocompleteList.innerHTML='';}});

async function searchShows(){
    const query=searchInput.value.trim();
    const lang=filterLanguage.value;
    const genre=filterGenre.value;
    let shows=[];
    if(query){
        try{
            const res=await fetch(`https://api.tvmaze.com/search/shows?q=${encodeURIComponent(query)}`);
            const data=await res.json();
            shows=data.map(item=>item.show);
        }catch(err){console.error(err);}
    }else{shows=popularShows;}
    if(lang) shows=shows.filter(s=>s.language===lang);
    if(genre) shows=shows.filter(s=>s.genres.includes(genre));
    mainContent.innerHTML='';
    mainContent.appendChild(createGridCardsSection('Search Results', shows));
}

// DETAIL PAGE
async function loadDetail(id){
    try{
        const res=await fetch(`https://api.tvmaze.com/shows/${id}?embed=episodes`);
        const show=await res.json();
        const episodes=show._embedded && show._embedded.episodes?show._embedded.episodes:[];
        const seasons=[...new Set(episodes.map(e=>e.season))].sort((a,b)=>a-b);
        mainContent.style.display='none';
        detailContainer.innerHTML='';
        const detailDiv=document.createElement('div');
        detailDiv.innerHTML=`
            <button class="back-btn" onclick="displayHomePage()">← Back</button>
            <div class="container">
                <div class="poster">
                    <img src="${show.image?show.image.original:'https://via.placeholder.com/300x450?text=No+Image'}" alt="${show.name}">
                </div>
                <div class="details">
                    <h2>${show.name}</h2>
                    <p><strong>Language:</strong> ${show.language||'-'}</p>
                    <p><strong>Status:</strong> ${show.status||'-'}</p>
                    <p><strong>Type:</strong> ${show.type||'-'}</p>
                    <p><strong>Premiered:</strong> ${show.premiered||'-'}</p>
                    <p><strong>Rating:</strong> ${show.rating && show.rating.average?show.rating.average:'-'}</p>
                    <p><strong>Network:</strong> ${show.network?show.network.name:(show.webChannel?show.webChannel.name:'-')}</p>
                    <p><strong>Seasons:</strong> ${seasons.length||'-'}</p>
                    <p><strong>Episodes:</strong> ${episodes.length||'-'}</p>
                    <div><strong>Genres:</strong> ${(show.genres||[]).map(g=>`<span class="genre-badge" style="background-color:${getUniqueColor(g)}">${g}</span>`).join('')}</div>
                    <div class="summary"><strong>Summary:</strong> ${show.summary||'No summary available.'}</div>
                    <p><strong>Official Site:</strong> ${show.officialSite?`<a href="${show.officialSite}" target="_blank">Visit</a>`:'-'}</p>
                    <div class="episode-list">
                        ${seasons.length>0 ? seasons.map(seasonNum=>{
                            const eps=episodes.filter(ep=>ep.season===seasonNum);
                            return `<div>
                                <div class="season-title">Season ${seasonNum}</div>
                                <div class="episode-header">
                                    <div>Ep</div><div>Name</div><div>Runtime</div>
                                </div>
                                ${eps.map(ep=>`<div class="episode-item" onclick="window.open('${ep.url}','_blank')">
                                    <div>${ep.number}</div>
                                    <div>${ep.name}</div>
                                    <div>${ep.runtime?ep.runtime+' min':''}</div>
                                </div>`).join('')}
                            </div>`;
                        }).join('') : `<div class="episode-item">Single movie / no episode</div>`}
                    </div>
                </div>
            </div>
        `;
        detailContainer.appendChild(detailDiv);
    }catch(err){console.error(err); detailContainer.innerHTML='<p>Failed to load show details.</p>';}
}

// INIT
const params=new URLSearchParams(window.location.search);
const showId=params.get('id');
if(showId){
    searchSection.style.display='none';
    mainContent.style.display='none';
    loadDetail(showId);
}else{
    loadPopularShows();
}
</script>
</body>
</html>