<template>
  <div style="height:100%">
    <x-header style="width:100%;position:fixed;left:0;top:0;z-index:100;"
      :left-options="leftOptions" title="位置信息">
      <span slot="overwrite-left">
        <x-icon type="close" size="20" class="icon-close" @click="close"></x-icon>
      </span>
    </x-header>
    <div id="baidumap" class="map">abc</div>
    <flexbox v-if="isEdit" :gutter="0" class="bottom-bar">
      <flexbox-item :span="6">
        <x-button type="primary" class="bottom-btn" :disabled="location.longitude === 0 && location.latitude === 0"
          @click.native="confirm"> 确定
        </x-button>
      </flexbox-item>
      <flexbox-item :span="6">
        <x-button type="default" class="bottom-btn" @click.native="close"> 取消
        </x-button>
      </flexbox-item>
    </flexbox>
  </div>
</template>

<script>
import { XHeader, Flexbox, FlexboxItem, XButton } from 'vux'
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
      defalult: ''
    },
    city: {
      type: String,
      defalult: '北京'
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
    Flexbox,
    FlexboxItem,
    XButton
  },
  data () {
    return {
      map: null,
      location: {
        city: '',
        address: '',
        longitude: 0,
        latitude: 0
      },
      leftOptions: {
        showBack: false
      }
    }
  },
  mounted () {
    let vm = this
    if (this.getPoint) {
      let geolocation = new BMap.Geolocation()
      geolocation.getCurrentPosition((r) => {
        if (geolocation.getStatus() === BMAP_STATUS_SUCCESS) {
          vm.location.longitude = r.point.lng
          vm.location.latitude = r.point.lat
        }
      })
    }
  },
  methods: {
    confirm () {
      if (this.longitude || this.latitude) {
        this.$emit('on-confirm', this.location)
      }
      this.close()
    },
    close () {
      if (this.map != null) {
        this.map.clearOverlays()
      }
      this.$emit('on-close')
    }
  },
  watch: {
    isShown (val) {
      if (!val) return
      let vm = this
      if (vm.map == null) {
        vm.map = new BMap.Map('baidumap')
        vm.map.enableScrollWheelZoom(true)
        vm.map.enableDoubleClickZoom(true)
        let geoc = new BMap.Geocoder()
        if (vm.isEdit) {
          vm.map.addEventListener('click', (e) => {
            vm.map.clearOverlays()
            let pt = e.point
            vm.location.longitude = pt.lng
            vm.location.latitude = pt.lat
            vm.location.city = ''
            vm.location.address = ''
            let marker = new BMap.Marker(pt)
            vm.map.addOverlay(marker)
            geoc.getLocation(pt, (rs) => {
              var addComp = rs.addressComponents
              vm.location.city = addComp.city
              vm.location.address = addComp.district + addComp.street + addComp.streetNumber
            })
          })
        }
      }
      setTimeout(() => {
        if (vm.longitude || vm.latitude) {
          vm.location.longitude = vm.longitude
          vm.location.latitude = vm.latitude
          vm.location.city = vm.city
          vm.location.address = vm.address
          let point = new BMap.Point(vm.longitude, vm.latitude)
          let marker = new BMap.Marker(point)
          vm.map.centerAndZoom(point, 14)
          vm.map.addOverlay(marker)
          if (vm.title || vm.address) {
            let opts = {
              width: 200,
              height: 70,
              title: vm.title
            }
            let infoWindow = new BMap.InfoWindow('地址：' + vm.address, opts)
            marker.addEventListener('click', () => {
              vm.map.openInfoWindow(infoWindow, point)
            })
          }
        } else if (vm.location.longitude || vm.location.latitude) {
          let point = new BMap.Point(vm.location.longitude, vm.location.latitude)
          vm.map.centerAndZoom(point, 14)
        } else if (vm.location.city) {
          vm.map.centerAndZoom(vm.location.city, 14)
        }
      }, 300)
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
  height:99%;
}
</style>
