<template>
  <div id="app">
    <header>
      <h1>COVID-19 STATS</h1>
    </header>
    <main>
      <div class="stats-block">
        <div class="select-block">
          <v-select :value="selectedItem" :options="locations" :reduce="location => location.id" @input="changeLocation" :getOptionLabel="location => location.name" placeholder="Choose a country"/>
        </div>
        <div class="content-block">
          <div v-if="isLoading">
            <Spinner :message=loadingText :size="32" :spacing="10" text-fg-color="#222" :font-size="14" class="spinner"/>
          </div>
          <div class="chart-block" v-if="selectedId !== '' && !chartIsLoading">
            <line-chart :chart-data="datacollection"></line-chart>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<script>
  import _ from 'lodash';
  import axios from 'axios';

  import LineChart from './LineChart/index';
  import Spinner from 'vue-simple-spinner';
  import VueSelect from './VueSelect';

  import locationsJson from './../data/countries.json';

  export default {
    name: 'app',
    components: {
      LineChart,
      Spinner,
      VueSelect,
    },
    data: () => ({
      isLoading: false,
      loadingText: 'Chart is loading...',

      chartColors: {
        confirmed: {
          borderColor: 'rgb(0,0,0,.45)',
          backgroundColor: 'rgb(0,0,0,.25)',
          pointBackgroundColor: 'rgb(0,0,0,.45)',
          pointBorderColor: 'rgb(0,0,0,.45)',
        },
        deaths: {
          borderColor: 'rgb(0,0,0,.85)',
          backgroundColor: 'rgb(0,0,0,.85)',
          pointBackgroundColor: 'rgb(0,0,0,.45)',
          pointBorderColor: 'rgb(0,0,0,.45)',
        },
        recovered: {
          borderColor: 'lightgreen',
          backgroundColor: 'lightgreen',
          pointBackgroundColor: 'lightgreen',
          pointBorderColor: 'lightgreen',
        }
      },
      chartIsLoading: false,
      datacollection: {},

      locations: locationsJson,
      selectedId: '',
      selectedItem: {},
    }),
    computed: {

    },
    mounted() {
      if (localStorage.country) {
        const item = this.getCountryById(localStorage.country);
        this.selectedId = localStorage.country;
        this.selectedItem = item;
        this.changeLocation(localStorage.country);
      }
    },
    created() {
      
    },
    watch: {
      selectedId(newVal) {
        if (newVal === null) {
          this.selectedId = '';
          this.selectedName = '';
          return;
        }

        localStorage.country = newVal;
        this.selectedItem = this.getCountryById(newVal);
      }
    },
    methods: {
      changeLocation(id) {
        if (id === null || id === '') {
          this.isLoading = false;
          this.chartIsLoading = false;
          return;
        }

        this.selectedId = id;
        this.isLoading = true;
        this.chartIsLoading = true;

        const chartData = {
          labels: [],
          datasets: [],
        };

        axios.get('https://zutix.ru/covid/country.php?id=' + id).then(res => {
          const data = res.data;
          const timelines = data.timelines;

          for (const key in timelines) {
            const vals = timelines[key];
            let saveLabels = (chartData.labels.length === 0);

            for (const valKey in vals) {
              //const valNumber = vals[valKey];
              //const date = new Date(Date.parse(valKey)).toLocaleDateString();

              if (saveLabels) {
                chartData.labels.push(valKey);
              }
            }

            chartData.datasets.push({
              label: key,
              borderColor: this.chartColors[key]['borderColor'],
              pointBackgroundColor: this.chartColors[key]['pointBackgroundColor'],
              borderWidth: 1,
              pointStyle: 'circle',
              radius: 1,
              hitRadius: 2,
              // fill: false,
              pointBorderColor: this.chartColors[key]['pointBorderColor'],
              backgroundColor: this.chartColors[key]['backgroundColor'],
              data: _.values(vals),
            });
          }

          this.datacollection = chartData;
          this.isLoading = false;
          this.chartIsLoading = false;
        });
      },
      findCountryNameById(id) {
        for (const location of this.locations) {
          if (location.id == id) {
            return location.name;
            break;
          }
        }

        return null;
      },
      getCountryById(id) {
        for (const location of this.locations) {
          if (location.id == id) {
            return location;
          }
        }

        return null;
      }
    }
  }
</script>

<style lang="less">
  @import (inline) '~must-have-css';
</style>

<style lang="less" scoped>
  #app {
    margin: 20px auto;
    width: 600px;

    @media screen and (max-device-width: 600px) {
      margin: 0;
      width: 100%;
      padding: 10px;
    }

    font-family: 'Roboto', sans-serif;
  }

  header {
    text-align: center;


    h1 {
      margin-bottom: 20px;
    }
  }

  main {
    .stats-block {
      > .select-block {
        text-align: center;
      }

      > .content-block {
        > .chart-block {
          margin: 30px 0;

          text-align: center;
        }
      }
    }
  }

  .spinner {
    margin-top: 20px;
  }
</style>
