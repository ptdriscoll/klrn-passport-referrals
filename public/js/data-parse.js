import {
  defaultColorsTrends,
  defaultBackgroundsTrends,
} from './charts-options.js';
import { setBarCharts, setTrendsCharts } from './charts-set.js';
import { makeJSON, errorsExist, makeDate } from './data-helpers.js';

/**
 * Parses data returned from getData in data-helpers.js. *
 * @param {object} data.
 *   data object: {
 *     availableDates: {min: '', max: '',},
 *     episodes: [],
 *     episodesTrends: [],
 *     errors: {},
 *     log: [],
 *     referralsTrends: [],
 *     requestedDates: {start: '', end: '', trendsStart: '',},
 *     shows: [],
 *     showsTrends: [],
 *   }
 */
export function parseData(data) {
  data = makeJSON(data);
  console.log(data);

  if (errorsExist(data)) return;

  const startInput = document.querySelector('#start-date');
  startInput.min = data.availableDates.min;
  startInput.max = data.availableDates.max;
  startInput.value = data.requestedDates.start;

  const endInput = document.querySelector('#end-date');
  endInput.min = data.availableDates.min;
  endInput.max = data.availableDates.max;
  endInput.value = data.requestedDates.end;

  let showsLabels = [];
  let showsData = [];
  let episodesLabels = [];
  let episodesData = [];
  let trendsShowsDatasets = [];
  let trendsEpisodesDatasets = [];
  let trendsReferralsData = [];
  let genresDataset = new Map([
    ['History', 0],
    ['Arts and Music', 0],
    ['Culture', 0],
    ['News and Public Affairs', 0],
    ['Drama', 0],
    ['Indie Films', 0],
    ['Science and Nature', 0],
    ['Food', 0],
    ['Home and How To', 0],
    ['Not Set', 0],
  ]);

  let trendsLabels = [];
  let trendDate = makeDate(data.requestedDates.trendsStart);
  const endDate = makeDate(data.requestedDates.end);

  while (trendDate <= endDate) {
    trendsLabels.push(new Date(trendDate));
    trendDate.setDate(trendDate.getDate() + 1);
  }

  //loop for top 10 shows and episodes
  for (let i = 0; i < 10; i++) {
    //add shows to chart data
    if (data.shows[i]) {
      showsLabels.push(data.shows[i]['Show']);
      showsData.push(data.shows[i].Pageviews);

      //add shows trends data
      if (i < 3 && data.showsTrending[i]) {
        let dataset = { data: new Array(trendsLabels.length).fill(0) };
        dataset.id = data.showsTrending[i]['ID'];
        dataset.label = data.showsTrending[i]['Show'];
        dataset.borderColor = defaultColorsTrends[i];
        dataset.backgroundColor = defaultBackgroundsTrends[i];
        dataset.fill = true;
        trendsShowsDatasets.push(dataset);
      }
    }

    //add episodes to chart data
    if (data.episodes[i]) {
      let episodeShow = data.episodes[i]['Show'];
      let episode = data.episodes[i]['Episode'];
      let label = episodeShow + ' - ' + episode;
      episodesLabels.push(label);
      episodesData.push(data.episodes[i].Pageviews);

      //add episodes trends data
      if (i < 3 && data.episodesTrending[i]) {
        let dataset = { data: new Array(trendsLabels.length).fill(0) };
        let episodeTrendingShow = data.episodesTrending[i]['Show'];
        let episodeTrending = data.episodesTrending[i]['Episode'];
        let labelTrending = episodeTrendingShow + ' - ' + episodeTrending;
        dataset.id = data.episodesTrending[i]['VideoID'];
        dataset.label = labelTrending;
        dataset.borderColor = defaultColorsTrends[i];
        dataset.backgroundColor = defaultBackgroundsTrends[i];
        dataset.fill = true;
        trendsEpisodesDatasets.push(dataset);
      }
    }
  }

  //loop through trendsLabels dates
  let showsIdx = 0;
  let episoIdx = 0;

  for (let i = 0; i < trendsLabels.length; i++) {
    let labelDate = trendsLabels[i];
    let showsDate, episodesDate;

    //Add pageviews to daily referrals dataset
    trendsReferralsData.push(data.referralsTrends[i].Pageviews);

    //set up first instance of a date for shows
    if (showsIdx < data.showsTrends.length) {
      showsDate = makeDate(data.showsTrends[showsIdx].Date);
    } else showsDate = null;

    //handle all instances of next date in shows
    while (showsDate && showsDate.getTime() === labelDate.getTime()) {
      let showID = data.showsTrends[showsIdx].ID;
      let show = trendsShowsDatasets.find((el) => el.id === showID);
      if (show) show.data[i] = data.showsTrends[showsIdx].Pageviews;

      //prepare to check for next date in shows
      showsIdx += 1;
      if (showsIdx < data.showsTrends.length) {
        showsDate = makeDate(data.showsTrends[showsIdx].Date);
      } else showsDate = null;
    }

    //set up first instance of a date for episodes
    if (episoIdx < data.episodesTrends.length) {
      episodesDate = makeDate(data.episodesTrends[episoIdx].Date);
    } else episodesDate = null;

    //handle all instances of next date in episodes
    while (episodesDate && episodesDate.getTime() === labelDate.getTime()) {
      let episodeID = data.episodesTrends[episoIdx].VideoID;
      let episode = trendsEpisodesDatasets.find((el) => el.id === episodeID);
      if (episode) episode.data[i] = data.episodesTrends[episoIdx].Pageviews;

      //prepare to check for next date in episodes
      episoIdx += 1;
      if (episoIdx < data.episodesTrends.length) {
        episodesDate = makeDate(data.episodesTrends[episoIdx].Date);
      } else episodesDate = null;
    }
  }

  //loop through all shows, and set genres and tables
  const tableShows = document.querySelector('#table-shows table tbody');
  const tableShowsRowFragment = document.createDocumentFragment();
  const tableRowTemplate = document.querySelector('#table-row-template');

  for (let i = 0; i < data.shows.length; i++) {
    //add up genres
    let genre = data.shows[i]['Genre'];
    genre = genre ? genre.trim().replace(/\s+/g, ' ') : 'Not Set';
    const pageviews = parseFloat(data.shows[i]['Pageviews']);
    const currViews = genresDataset.has(genre) ? genresDataset.get(genre) : 0;
    genresDataset.set(genre, currViews + pageviews);

    //add table shows rows
    let row = document.importNode(tableRowTemplate.content, true);
    let cols = row.querySelectorAll('td');
    cols[0].textContent = i + 1;
    cols[1].textContent = data.shows[i]['Show'];
    cols[2].textContent = genre;
    cols[3].textContent = data.shows[i]['Pageviews'];
    cols[4].textContent = data.shows[i]['Users'];
    cols[5].textContent = data.shows[i]['Duration'];
    tableShowsRowFragment.append(row);
  }

  //loop through all episodes
  const tableEpisodes = document.querySelector('#table-episodes table tbody');
  const tableEpisodesRowFragment = document.createDocumentFragment();

  for (let i = 0; i < data.episodes.length; i++) {
    //add table episodes rows
    let row = document.importNode(tableRowTemplate.content, true);
    let cols = row.querySelectorAll('td');
    cols[0].textContent = i + 1;
    cols[1].textContent = data.episodes[i]['Show'];
    cols[2].textContent = data.episodes[i]['Episode'];
    cols[3].textContent = data.episodes[i]['Pageviews'];
    cols[4].textContent = data.episodes[i]['Users'];
    cols[5].textContent = data.episodes[i]['Duration'];
    tableEpisodesRowFragment.append(row);
  }

  //set total referrals
  const totalReferrals = data.shows.reduce(
    (acc, show) => acc + parseInt(show.Pageviews),
    0
  );
  document.querySelector('#total-referrals').innerHTML = totalReferrals;

  //set bar charts
  setBarCharts(
    genresDataset,
    showsLabels,
    showsData,
    episodesLabels,
    episodesData
  );

  //set trend charts
  setTrendsCharts(
    trendsLabels,
    trendsShowsDatasets,
    trendsEpisodesDatasets,
    trendsReferralsData
  );

  //set tables
  while (tableShows.firstChild) {
    tableShows.removeChild(tableShows.firstChild);
  }

  while (tableEpisodes.firstChild) {
    tableEpisodes.removeChild(tableEpisodes.firstChild);
  }

  tableShows.append(tableShowsRowFragment);
  tableEpisodes.append(tableEpisodesRowFragment);
}
