const showsTable = document.querySelector('#table-shows table');
const showsTableHeaders = showsTable.querySelectorAll('thead tr th');

showsTableHeaders.forEach((el, i) =>
  el.addEventListener('click', (ev) => tableSort(showsTable, i))
);

const episodesTable = document.querySelector('#table-episodes table');
const episodesTableHeaders = episodesTable.querySelectorAll('thead tr th');

episodesTableHeaders.forEach((el, i) =>
  el.addEventListener('click', (ev) => tableSort(episodesTable, i))
);

/**
 * Sorts table rows in place.
 * @param {HTMLElement} table - Table to sort.
 * @param {number} col - Column that was clicked to base sort on.
 */
function tableSort(table, col) {
  let rows = table.rows;
  let toSort = [];

  //put row content and indices, except rows[0], into array of arrays
  for (let i = 1; i < rows.length; i++) {
    let content = rows[i].getElementsByTagName('TD')[col].innerHTML;
    if (col === 1 || col === 2) content = content.toLowerCase();
    else content = Number(content);
    toSort.push([content, i]);
  }

  //now sort array of two-item arrays using insertion sort
  //start with ascending, but then reverse if array was already sorted as ascending
  let [result, wasAscSorted] = insertionSort(toSort, true);
  if (wasAscSorted) {
    [result, wasAscSorted] = insertionSort(toSort, false);
  }

  //use sorted result to sort table in dom
  //use i - 1 as index in result array, since result does not include header row
  let rowsCopy = table.cloneNode(true).rows;
  let rowCopy;
  for (let i = 1; i < rows.length; i++) {
    rowCopy = rowsCopy[result[i - 1][1]].cloneNode(true);
    rowCopy.querySelector('td').innerHTML = i;
    rows[i].replaceWith(rowCopy);
  }
  rowsCopy = null;
  rowCopy = null;
}

/**
 * Uses insertion sort to sort an array of two-item arrays by the first item.
 * @param {Array} array - Array of two-item arrays.
 *   Example: [
 *     ['all creatures great and small', 10],
 *     ['american masters', 36],
 *     ['austin city limits', 16],
 *   ]
 * @param {boolean} asc - If true, sort as ascending, otherwise sort as descending.
 * @return {boolean} - Reports whether provided array was already sorted as ascending.
 *   When wasAscSorted is returned as true, then invoking code can run again with asc = false.
 */
function insertionSort(array, asc = true) {
  let wasAscSorted = true;

  for (let i = 1; i < array.length; i++) {
    let curr = array[i];
    let j = i - 1;

    if (asc) {
      while (j >= 0 && array[j][0] > curr[0]) {
        array[j + 1] = array[j];
        wasAscSorted = false;
        j--;
      }
    } else {
      while (j >= 0 && array[j][0] < curr[0]) {
        array[j + 1] = array[j];
        j--;
      }
    }

    array[j + 1] = curr;
  }
  return [array, wasAscSorted];
}
