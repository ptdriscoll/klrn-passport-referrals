import {
  defaultColors,
  defaultColorsTrends,
  defaultBackgroundsTrends,
  ctxBarShows,
  ctxBarEpisodes,
  ctxBarGenres,
  barOptions,
  barOptionsEpisodes,
  barOptionsGenres,
  ctxTrendsShows,
  ctxTrendsEpisodes,
  ctxTrendsReferrals,
  tooltipOptions,
  trendsOptions,
} from './charts-options.js';

/**
 * Sets bar charts for shows, episodes and genres.
 *
 * @param {Map} genresDataset - Example item: {'Drama' => 84}.
 * @param {Array} showsLabels - Show titles.
 * @param {Array} showsData - Show pageviews in same order as show titles.
 * @param {Array} episodesLabels - Episode titles.
 * @param {Array} episodesData - Episode pageviews in same order and episode titles.
 */
export function setBarCharts(
  genresDataset,
  showsLabels,
  showsData,
  episodesLabels,
  episodesData
) {
  let chartBarShows = Chart.getChart(ctxBarShows);
  if (chartBarShows) chartBarShows.destroy();

  let chartBarGenres = Chart.getChart(ctxBarGenres);
  if (chartBarGenres) chartBarGenres.destroy();

  const genresDatasetArray = [...genresDataset.entries()];
  const genresDatasetSorted = new Map(
    genresDatasetArray.sort((a, b) => b[1] - a[1])
  );

  //set shows
  if (showsLabels.length > 0) {
    chartBarShows = new Chart(ctxBarShows, {
      type: 'bar',
      data: {
        labels: showsLabels,
        datasets: [
          {
            label: 'Total Referrals',
            data: showsData,
            backgroundColor: defaultColors[0],
            borderWidth: 0,
          },
        ],
      },
      options: barOptions,
    });

    //set genres
    chartBarGenres = new Chart(ctxBarGenres, {
      type: 'bar',
      data: {
        labels: [...genresDatasetSorted.keys()],
        datasets: [
          {
            label: 'Total Referrals',
            data: [...genresDatasetSorted.values()],
            backgroundColor: defaultColors[0],
            borderWidth: 0,
          },
        ],
      },
      options: barOptionsGenres,
    });

    document.querySelector('.graphic.bar.shows').classList.remove('hide');
    document.querySelector('.graphic.bar.genres').classList.remove('hide');
  }

  let chartBarEpisodes = Chart.getChart(ctxBarEpisodes);
  if (chartBarEpisodes) chartBarEpisodes.destroy();

  //set episodes
  if (episodesLabels.length > 0) {
    chartBarEpisodes = new Chart(ctxBarEpisodes, {
      type: 'bar',
      data: {
        labels: episodesLabels,
        datasets: [
          {
            label: 'Total Referrals',
            data: episodesData,
            backgroundColor: defaultColors[0],
            borderWidth: 0,
          },
        ],
      },
      options: barOptionsEpisodes,
    });

    document.querySelector('.graphic.bar.episodes').classList.remove('hide');
  }
}

/**
 * Sets timeline charts for shows, episodes and total referrals.
 *
 * @param {Array} trendsLabels - The Dates for timeline labels.
 * @param {Array} trendsShowsDatasets - Datasets for shows,
 *   including titles, pageviews over time, and styles.
 * @param {Array} trendsEpisodesDatasets - Datasets for episodes,
 *   including titles, pageviews over time, and styles.
 * @param {Array} trendsReferralsData - Total referrals pageviews.
 *
 * Example dataset as needed by Chart.js for a timeline: [{
 *   label: 'Call the Midwife',
 *   data: ['3', 0, 0, '1', 0, 0, '1', '1',],
 *   borderColor: 'rgb(255, 99, 132, 0.5)',
 *   backgroundColor: 'rgb(255, 99, 132, 0.05)',
 *   fill: true,
 * ]}
 */
export function setTrendsCharts(
  trendsLabels,
  trendsShowsDatasets,
  trendsEpisodesDatasets,
  trendsReferralsData
) {
  //date range of trends data
  const now = new Date();
  const firstDay = trendsLabels[0];
  const lastDay = trendsLabels[trendsLabels.length - 1];

  //how many days old last data point is
  const oneDay = 1000 * 60 * 60 * 24;
  const timeAgo = now.getTime() - lastDay.getTime();
  const daysAgo = Math.floor(timeAgo / oneDay);

  //format dates based on recent or old data is
  if (daysAgo > 6) {
    trendsOptions.scales.x.time.displayFormats.day = 'MMM d';
    if (firstDay.getFullYear() < now.getFullYear()) {
      trendsOptions.scales.x.time.displayFormats.day += ', yyyy';
    }
  }

  //set trends, and daily referrals charts
  let trendsOptionsEpisodes = JSON.parse(JSON.stringify(trendsOptions));
  trendsOptionsEpisodes.plugins.title.text = 'Top Episodes Trends';
  trendsOptionsEpisodes.plugins.tooltip = tooltipOptions;

  let trendsOptionsReferrals = JSON.parse(
    JSON.stringify(trendsOptionsEpisodes)
  );
  trendsOptionsReferrals.plugins.title.text = 'Daily Referrals';
  trendsOptionsReferrals.plugins.tooltip = tooltipOptions;
  trendsOptionsReferrals.plugins.legend = { display: false };

  let chartTrendsShows = Chart.getChart(ctxTrendsShows);
  if (chartTrendsShows) chartTrendsShows.destroy();

  let chartTrendsEpisodes = Chart.getChart(ctxTrendsEpisodes);
  if (chartTrendsEpisodes) chartTrendsEpisodes.destroy();

  let chartTrendsReferrals = Chart.getChart(ctxTrendsReferrals);
  if (chartTrendsReferrals) chartTrendsReferrals.destroy();

  if (trendsLabels.length > 1) {
    //set shows
    chartTrendsShows = new Chart(ctxTrendsShows, {
      type: 'line',
      data: {
        labels: trendsLabels,
        datasets: trendsShowsDatasets,
      },
      options: trendsOptions,
    });

    //set episodes
    chartTrendsEpisodes = new Chart(ctxTrendsEpisodes, {
      type: 'line',
      data: {
        labels: trendsLabels,
        datasets: trendsEpisodesDatasets,
      },
      options: trendsOptionsEpisodes,
    });

    //set total referrals
    chartTrendsReferrals = new Chart(ctxTrendsReferrals, {
      type: 'line',
      data: {
        labels: trendsLabels,
        datasets: [
          {
            data: trendsReferralsData,
            borderColor: defaultColorsTrends[1],
            backgroundColor: defaultBackgroundsTrends[1],
            fill: true,
          },
        ],
      },
      backgroundColor: 'rgba(255, 26, 104, 0.2)',
      options: trendsOptionsReferrals,
    });

    const graphicsTrends = [...document.querySelectorAll('.graphic.trends')];
    graphicsTrends.forEach((g) => g.classList.remove('hide'));
  }
}
