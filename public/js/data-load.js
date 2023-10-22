import { parseData } from './data-parse.js';
import { getData, fadeInMain } from './data-helpers.js';

getData()
  .then((res) => parseData(res))
  .then(() => fadeInMain())
  .catch((err) => console.log(err));
