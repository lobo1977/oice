<template>
  <div style="height:100%;padding-top:46px;">
    <x-header class="fix-top"
      :left-options="leftOptions" title="位置信息">
      <span slot="overwrite-left">
        <x-icon type="close" size="20" class="icon-close" @click="close"></x-icon>
      </span>
      <span slot="right" v-if="isEdit" @click="confirm"
        :class="{enable: location.longitude !== 0 || location.latitude !== 0}">
        确定
      </span>
    </x-header>

    <search v-if="isEdit"
      @result-click="resultClick"
      @on-change="searchMap"
      :results="searchResults"
      position="absolute"
      auto-scroll-to-top top="46px"
      @on-focus="onFocus"
      @on-cancel="onCancel"
      @on-submit="onSubmit"
      ref="searchAddress"></search>
    
    <div id="baidumap" class="map"></div>

    <div v-if="!isEdit" class="fix-bottom">
      <x-button mini type="primary" @click.native="goNative" 
        :disabled="location.latitude === 0 && title === ''" 
        style="float:right;margin-right:30px;">导航</x-button>
      <h4 style="font-size:1em;">{{title}}</h4>
      <span style="font-size:0.9em;">{{'地址：' + district + address}}</span>
      <form ref="frmGoMap" action="https://api.map.baidu.com/direction" target="_blank" method="get">
        <input type="hidden" name="region" :value="location.city">
        <input type="hidden" name="output" value="html" />
        <input type="hidden" name="mode" value="driving">
        <input type="hidden" name="origin" :value="originPoint.lat + ',' + originPoint.lng">
        <input type="hidden" name="destination" 
          :value="distination" />
        <input type="hidden" name="src" value="m.o-ice.com">
      </form>
    </div>
  </div>
</template>

<script>
import { XHeader, Search } from 'vux'
import BMap from 'BMap'
import BMAP_STATUS_SUCCESS from 'BMAP_STATUS_SUCCESS'

export default {
  name: 'baidumap',
  props: {
    isShown: {
      type: Boolean,
      default: false
    },
    isEdit: {
      type: Boolean,
      default: false
    },
    getPoint: {
      type: Boolean,
      default: false
    },
    title: {
      type: String,
      default: ''
    },
    city: {
      type: String,
      default: '北京'
    },
    district: {
      type: String,
      defalult: ''
    },
    address: {
      type: String,
      defalult: ''
    },
    longitude: {
      type: Number,
      default: 0
    },
    latitude: {
      type: Number,
      default: 0
    }
  },
  components: {
    XHeader,
    Search
  },
  data () {
    return {
      map: null,
      originPoint: {
        lat: 0,
        lng: 0
      },
      localSearch: null,
      location: {
        city: '',
        district: '',
        address: '',
        longitude: 0,
        latitude: 0
      },
      leftOptions: {
        showBack: false
      },
      isSearching: false,
      searchResults: []
    }
  },
  mounted () {
  },
  methods: {
    confirm () {
      if (this.location.longitude || this.location.latitude) {
        this.$emit('on-confirm', this.location)
      }
      this.close()
    },
    close () {
      if (this.map != null) {
        this.map.clearOverlays()
      }
      this.$emit('on-close')
    },
    onFocus () {
      this.isSearching = true
    },
    onCancel () {
      this.isSearching = false
    },
    onSubmit () {
      this.$refs.searchAddress.setBlur()
      this.isSearching = false
    },
    searchMap (val) {
      if (this.localSearch != null) {
        this.localSearch.search(val)
      }
    },
    searchComplete (result) {
      if (this.localSearch.getStatus() === BMAP_STATUS_SUCCESS) {
        this.searchResults = []
        if (result) {
          let resultCount = result.getCurrentNumPois()
          for (let i = 0; i < resultCount; i++) {
            this.searchResults.push(result.getPoi(i))
          }
        }
      }
    },
    resultClick (item) {
      if (this.map != null && item != null) {
        let marker = new BMap.Marker(item.point)
        this.map.clearOverlays()
        this.map.addOverlay(marker)
        this.map.centerAndZoom(item.point, 16)
        this.location.longitude = item.point.lng
        this.location.latitude = item.point.lat
        this.location.address = item.address.replace(item.city, '')
        this.location.city = item.city
        this.$refs.searchAddress.setBlur()
        this.isSearching = false
      }
    },
    goNative () {
      let vm = this
      if (vm.originPoint.lat && vm.originPoint.lng) {
        vm.$refs.frmGoMap.submit()
      } else if (vm.$isWechat()) {
        vm.$getLocation((data) => {
          let convertor = new BMap.Convertor()
          let pointArr = []
          pointArr.push(new BMap.Point(data.longitude, data.latitude))
          convertor.translate(pointArr, 3, 5, (result) => {
            if (result.status === 0) {
              vm.originPoint = result.points[0]
              setTimeout(() => {
                vm.$refs.frmGoMap.submit()
              }, 300)
            }
          })
        })
      } else {
        let geolocation = new BMap.Geolocation()
        geolocation.getCurrentPosition((r) => {
          if (geolocation.getStatus() === BMAP_STATUS_SUCCESS) {
            vm.originPoint = r.point
            setTimeout(() => {
              vm.$refs.frmGoMap.submit()
            }, 300)
          }
        })
      }
    }
  },
  watch: {
    isShown (val) {
      if (!val) return
      let vm = this

      vm.location.longitude = vm.longitude
      vm.location.latitude = vm.latitude
      vm.location.city = vm.city
      vm.location.district = vm.district
      vm.location.address = vm.address

      if (vm.map == null) {
        vm.map = new BMap.Map('baidumap')
        vm.map.enableScrollWheelZoom(true)
        vm.map.enableDoubleClickZoom(true)
        vm.localSearch = new BMap.LocalSearch(vm.location.city, {
          onSearchComplete: vm.searchComplete
        })

        if (vm.isEdit) {
          let geoc = new BMap.Geocoder()
          vm.map.addEventListener('click', (e) => {
            vm.map.clearOverlays()
            let pt = e.point
            vm.location.longitude = pt.lng
            vm.location.latitude = pt.lat
            let marker = new BMap.Marker(pt)
            vm.map.addOverlay(marker)
            geoc.getLocation(pt, (rs) => {
              var addComp = rs.addressComponents
              if (addComp.city) {
                vm.location.city = addComp.city
              }
              if (addComp.district) {
                vm.location.district = addComp.district
              }
              if (addComp.street || addComp.streetNumber) {
                vm.location.address = addComp.street + addComp.streetNumber
              }
            })
          })
        }
      }

      setTimeout(() => {
        if (vm.location.longitude || vm.location.latitude) {
          let point = new BMap.Point(vm.location.longitude, vm.location.latitude)
          let marker = new BMap.Marker(point)
          vm.map.centerAndZoom(point, 14)
          vm.map.addOverlay(marker)
        } else if (vm.location.address || vm.title) {
          let address = vm.location.city + vm.location.district + vm.location.address + vm.title
          let geo = new BMap.Geocoder()
          geo.getPoint(address, (point) => {
            if (point) {
              vm.map.centerAndZoom(point, 16)
              vm.map.addOverlay(new BMap.Marker(point))
            } else {
              vm.map.centerAndZoom(vm.location.city, 14)
            }
          }, vm.location.city)
        } else if (vm.originPoint.lat && vm.originPoint.lng) {
          vm.map.centerAndZoom(vm.originPoint, 16)
        } else {
          vm.map.centerAndZoom(vm.location.city, 14)
        }
      }, 300)
    }
  },
  computed: {
    distination () {
      if (this.location.latitude && this.location.longitude && this.title) {
        return 'latlng:' + this.location.latitude + ',' + this.location.longitude + '|name:' + this.title
      } else if (this.location.latitude && this.location.longitude) {
        return this.location.latitude + ',' + this.location.longitude
      } else {
        return this.title
      }
    }
  }
}
</script>

<style>
.icon-close {
  position:relative;
  fill:#ccc;
}
.map {
  height:100%;
}

.fix-top {
  position:fixed;
  top:0;
  z-index:100;
  width:100%;
}

.fix-bottom {
  position:fixed;
  bottom:0;
  width:100%;
  background-color:#fff;
  padding:10px 15px;
}

.enable {
  color:#fff;
}
</style>
