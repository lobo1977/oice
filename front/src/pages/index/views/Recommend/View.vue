<template>
  <div>
    <div style="padding:15px 15px 0 15px;">
      <h3>呈送：{{customer.customer_name}}</h3>
      <p style="margin:20px 0 30px 0;font-size:0.8em;">
        <span v-if="company.full_name">代理行：{{company.full_name}}<br /></span>
        <span v-if="manager.title">客户经理：{{manager.title}} &nbsp;</span>
        <span v-if="manager.mobile">{{manager.mobile}} &nbsp;</span>
        <br />制作日期：{{date|formatDate}}
      </p>
      <p style="text-align:center;font-size:0.8em">声明</p>
      <p style="font-size:0.8em">此份报告的内容是{{company.title}}与讨论此报告特定用途的收件人之间的机密文件。此份报告中的有关资料以及数据取自认为可靠的来源；但是由于市场的迅速变化，{{company.title}}对于本报告中提供予第三方的部分或全部内容并不承担任何的责任。未经{{company.title}}事先的书面许可不得将此报告的全部或部分内容或格式刊登于任何的文件，声明或传阅或透露给任何第三方。</p>
    </div>

    <div v-transfer-dom>
      <previewer ref="prevRecommend" :list="images" :options="previewOptions"></previewer>
    </div>

    <load-more :show-loading="false" background-color="#fbf9fe"></load-more>

    <div v-for="(info, index) in list" :key="index">
      <h4 style="margin:0 15px;">{{info.building_name}}</h4>
      <p style="margin:8px 15px 0 15px;" v-if="info.desc">{{info.desc}}</p>
      <group gutter="10px" label-width="4em" label-margin-right="1em">
        <cell title="图片" is-link v-if="info.images && info.images.length" @click.native="preview(index)">
          <img class="th-image" v-for="(img, i) in info.images" :key="i" v-show="i < 3" :src="img.msrc" />
        </cell>
        <cell title="地址" value-align="left" :value="info.address" v-if="info.area + info.address"
          :is-link="(info.longitude || info.latitude) != 0" @click.native="openMap(index)"></cell>
        <cell title="竣工日期" value-align="left" :value="info.completion_date|formatDate" v-if="info.completion_date"></cell>
        <cell title="总建筑面积" value-align="left" :value="info.acreage" v-show="info.acreage"></cell>
        <!-- <cell title="楼层" value-align="left" :value="info.building_floor" v-if="info.building_floor"></cell>
        <cell title="层面积" value-align="left" :value="info.floor_area + ' 平方米'" v-if="info.floor_area > 0"></cell> -->
        <cell title="层高" value-align="left" :value="info.floor_height + ' 米'" v-if="info.floor_height > 0"></cell>
        <cell title="楼板承重" value-align="left" :value="info.bearing + ' 千克/平方米'" v-if="info.bearing > 0"></cell>
        <cell title="开发商" value-align="left" :value="info.developer" v-if="info.developer"></cell>
        <cell title="物业管理" value-align="left" :value="info.manager" v-if="info.manager"></cell>
        <cell title="物业费" value-align="left" :value="info.fee" v-if="info.fee"></cell>
        <!-- <cell title="电费" value-align="left" :value="info.electricity_fee" v-if="info.electricity_fee"></cell>
        <cell title="停车位" value-align="left" :value="info.car_seat" v-if="info.car_seat"></cell> -->
      </group>

      <panel header="建议单元" :list="info.units" type="1"></panel>

      <group title="项目说明" v-if="info.rem">
        <p class="group-padding">{{ info.rem }}</p>
      </group>
      <group title="交通状况" v-if="info.traffic">
        <p class="group-padding">{{ info.traffic }}</p>
      </group>
      <group title="楼宇设备" v-if="info.equipment">
        <p class="group-padding">{{ info.equipment }}</p>
      </group>
      <group title="配套设施" v-if="info.facility">
        <p class="group-padding">{{ info.facility }}</p>
      </group>
      <group title="周边环境" v-if="info.environment">
        <p class="group-padding">{{ info.environment }}</p>
      </group>

      <load-more :show-loading="false" background-color="#fbf9fe"></load-more>
    </div>

    <div style="margin-bottom:15px;text-align:center;">
      <img v-if="company.logo" :src="company.logo" style="margin-bottom:5px;width:200px;" />
      <p style="font-size:0.8em">{{company.rem}}</p>
    </div>

    <popup v-model="showMap" position="bottom" height="100%" style="overflow-y:hidden;">
      <baidumap :get-point="false"
        :is-shown="showMap"
        :title="map_title"
        :district="map_district"
        :address="map_address"
        :longitude="longitude" 
        :latitude="latitude" 
        @on-close="closeMap"></baidumap>
    </popup>
  </div>
</template>

<script>
import { Previewer, TransferDom, XTable } from 'vux'
import Baidumap from '@/components/BaiduMap.vue'

export default {
  directives: {
    TransferDom
  },
  components: {
    Previewer,
    XTable,
    Baidumap
  },
  data () {
    return {
      id: 0,
      customer: {
        title: '',
        customer_name: ''
      },
      company: {
        full_name: ''
      },
      manager: {
        title: '',
        mobile: ''
      },
      date: '',
      list: [],
      images: [],
      previewOptions: {
      },
      showMap: false,
      map_title: '',
      map_district: '',
      map_address: '',
      longitude: 0,
      latitude: 0
    }
  },
  methods: {
    preview (index) {
      let vm = this
      vm.images = vm.list[index].images
      if (vm.images.length) {
        setTimeout(() => {
          vm.$refs.prevRecommend.show(0)
        }, 200)
      }
    },
    openMap (index) {
      let vm = this
      if (vm.list[index].longitude || vm.list[index].latitude) {
        vm.map_title = vm.list[index].building_name
        vm.map_district = vm.list[index].area
        vm.map_address = vm.list[index].address
        vm.longitude = vm.list[index].longitude
        vm.latitude = vm.list[index].latitude
        vm.showMap = true
      }
    },
    closeMap () {
      this.showMap = false
    }
  },
  beforeRouteEnter (to, from, next) {
    next(vm => {
      if (to.params.id) {
        vm.id = to.params.id
        if (vm.id) {
          vm.$get('/api/customer/show?id=' + vm.id, (res) => {
            if (res.success) {
              vm.customer = res.data.customer
              vm.company = res.data.company
              vm.manager = res.data.manager
              vm.date = res.data.date
              vm.list = res.data.list

              if (vm.$isWechat()) {
                let shareTitle = vm.company.title + '物业推荐'
                let shareLink = window.location.href
                let shareDesc = '呈送：' + vm.customer.customer_name + ' 客户经理:' + vm.manager.title
                let shareImage = null

                if (vm.list[0].images.length) {
                  shareImage = window.location.protocol + '//' +
                    window.location.host + vm.list[0].images[0].msrc
                }

                vm.$wechatShare(null, shareLink, shareTitle, shareDesc, shareImage)
              }
            }
          })
        }
      }
    })
  }
}
</script>

<style lang="less">
  .th-image {
    display:inline-block;
    margin-left:5px;
    margin-right:0;
    width:auto;
    height:50px;
    vertical-align: middle;
  }
  td.unit {
    padding:5px 15px;
    line-height:1.3em;
    .title {
      text-align:left;
      margin-bottom:5px;
    }
    .desc {
      margin:0;
      text-align:left;
      font-size:0.8em;
      color:#aaa;
    }
  }
</style>