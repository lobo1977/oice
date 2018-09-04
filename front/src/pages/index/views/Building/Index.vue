<template>
  <div>
    <search
      @result-click="resultClick"
      @on-change="getResult"
      :results="results"
      position="absolute"
      auto-scroll-to-top top="46px"
      @on-focus="onFocus"
      @on-cancel="onCancel"
      @on-submit="onSubmit"
      ref="search"></search>
    
    <actionsheet v-model="showNewMenu" :menus="newMenus" theme="android" @on-click-menu="newMenuClick"></actionsheet>

    <div v-show="!isSearching">
      <sticky :offset="46">
        <flexbox :gutter="0" class="filter">
          <flexbox-item>
            <x-button :plain="true" @click.native="showTypePicker = true" style="border-left:0">
              <span solt="default">{{typeText}} 
                <x-icon type="arrow-down-b" size="12"></x-icon>
              </span>
            </x-button>
          </flexbox-item>
          <flexbox-item>
            <x-button :plain="true" @click.native="showDistrictPicker = true">
              <span solt="default">{{districtText}}
                <x-icon type="arrow-down-b" size="12"></x-icon>
              </span>
            </x-button>
          </flexbox-item>
          <flexbox-item>
            <x-button :plain="true" @click.native="showRentSellPicker = true">
              <span solt="default">{{rentSellText}}
                <x-icon type="arrow-down-b" size="12"></x-icon>
              </span>
            </x-button>
          </flexbox-item>
          <flexbox-item>
            <x-button :plain="true" @click.native="showAcreagePicker = true">
              <span solt="default">{{acreageText}}
                <x-icon type="arrow-down-b" size="12"></x-icon>
              </span>
            </x-button>
          </flexbox-item>
        </flexbox>
      </sticky>

      <panel :list="buildingList" :type="listType" @on-img-error="onImgError" style="margin-top:0"></panel>

      <popup-picker ref="typePicker" class="popup-picker" :show.sync="showTypePicker" 
        :show-cell="false" :data="typePickerList"  
        @on-change="typeChange" v-model="typeValue"></popup-picker>
      <popup-picker ref="districtPicker" class="popup-picker" :show.sync="showDistrictPicker" 
        :show-cell="false" :data="districtPickerList" :columns=2 
        @on-change="districtChange" v-model="districtValue"></popup-picker>
      <popup-picker ref="rentSellPicker" class="popup-picker" :show.sync="showRentSellPicker" 
        :show-cell="false" :data="rentSellPickerList" 
        @on-change="rentSellChange" v-model="rentSellValue"></popup-picker>
      <popup-picker ref="acreagePicker" class="popup-picker" :show.sync="showAcreagePicker" 
        :show-cell="false" :data="acreagePickerList" 
        @on-change="acreageChange" v-model="acreageValue"></popup-picker>

      <div style="height:50px;">
        <load-more :show-loading="isLoading" v-show="isLoading || isEnd" :tip="loadingTip"></load-more>
      </div>
    </div>
  </div>
</template>

<script>
import { Search } from 'vux'
import { mapState } from 'vuex'
import typeData from '../../data/building_type.json'
import districtData from '../../data/beijing_area.json'
import rentSellData from '../../data/rent_sell.json'
import acreageData from '../../data/acreage.json'

export default {
  components: {
    Search
  },
  data () {
    return {
      showNewMenu: false,
      newMenus: {
        new: '添加项目',
        template: '下载批量导入模板',
        import: '批量导入项目'
      },
      isLoading: false,
      isSearching: false,
      typeText: '类别',
      typeValue: [],
      districtText: '区域',
      districtValue: [],
      rentSellText: '租售',
      rentSellValue: [],
      acreageText: '面积',
      acreageValue: [],
      showTypePicker: false,
      showDistrictPicker: false,
      showRentSellPicker: false,
      showAcreagePicker: false,
      typePickerList: [typeData],
      districtPickerList: districtData,
      rentSellPickerList: [rentSellData],
      acreagePickerList: [acreageData],
      listType: '5',
      page: 0,
      isEnd: false,
      buildingList: [],
      results: []
    }
  },
  beforeRouteEnter (to, from, next) {
    next(vm => {
      if (vm.$isWechat()) {
        let shareTitle = document.title
        let shareLink = window.location.href
        let shareDesc = '商用写字楼经纪人助理'
        let shareImage = window.location.protocol + '//' +
            window.location.host + '/static/img/logo.png'

        vm.$wechatShare(null, shareLink, shareTitle, shareDesc, shareImage)
      }
    })
  },
  methods: {
    // Searcher
    resultClick (item) {
      this.$router.push({name: 'BuildingView', params: {id: item.id}})
      this.$refs.search.setBlur()
      this.isSearching = false
    },
    getResult (val) {
      if (val) {
        this.$post('/api/building/index', {
          keyword: val
        }, (res) => {
          if (res.success) {
            this.results = res.data
          } else {
            this.results = []
          }
        })
      } else {
        this.results = []
      }
    },
    onFocus () {
      this.isSearching = true
    },
    onSubmit () {
      this.$refs.search.setBlur()
      this.isSearching = false
    },
    onCancel () {
      this.isSearching = false
    },
    new () {
      // this.showNewMenu = true
      this.$router.push({name: 'BuildingEdit', params: {id: 0}})
    },
    newMenuClick (key, item) {
      if (key === 'new') {
        this.$router.push({name: 'BuildingEdit', params: {id: 0}})
      }
    },
    // Picker
    typeChange (val) {
      this.typeText = this.$refs.typePicker.getNameValues()
      this.loadListData(true)
    },
    districtChange (val) {
      this.districtText = this.$refs.districtPicker.getNameValues()
      this.loadListData(true)
    },
    rentSellChange (val) {
      this.rentSellText = this.$refs.rentSellPicker.getNameValues()
      this.loadListData(true)
    },
    acreageChange (val) {
      this.acreageText = this.$refs.acreagePicker.getNameValues()
      this.loadListData(true)
    },
    setTypePicker (index) {
      if (index >= 0 && index < this.typePickerList[0].length) {
        this.typeText = this.typePickerList[0][index].name
        this.typeValue = [this.typePickerList[0][index].value]
      }
    },

    // list
    onImgError (item, $event) {
    },

    loadListData (empty) {
      if (empty) {
        this.isEnd = false
        this.page = 1
        this.buildingList = []
      }

      this.isLoading = true

      this.$post('/api/building/index', {
        page: this.page,
        type: (this.typeValue.length ? this.typeValue[0] : ''),
        area: (this.districtValue.length ? this.districtValue[0] : ''),
        district: (this.districtValue.length > 1 ? this.districtValue[1] : ''),
        rent_sell: (this.rentSellValue.length && this.rentSellValue[0] !== '出租,出售' ? this.rentSellValue[0] : ''),
        acreage: (this.acreageValue.length ? this.acreageValue[0] : '0')
      }, (res) => {
        this.isLoading = false

        if (res.success) {
          let newData = res.data

          if (!newData || newData.length < 10) {
            this.isEnd = true
          }
          for (let item in newData) {
            this.buildingList.push(newData[item])
          }
        }
      })
    }
  },
  mounted: function () {
    if (this.$route.query && this.$route.query.type) {
      let i = Number(this.$route.query.type)
      this.setTypePicker(i)
    } else {
      this.setTypePicker(1)
    }
  },
  watch: {
    scrollBottom (isBottom) {
      if (isBottom && this.$route.name === 'Building' &&
        !this.isLoading && !this.isEnd) {
        this.page++
        this.loadListData()
      }
    },
    $route (to) {
      // if (to.query.type) {
      //   let i = Number(to.query.type)
      //   this.setTypePicker(i)
      // } else {
      //   this.setTypePicker(1)
      // }
    }
  },
  computed: {
    ...mapState({
      scroolTop: state => state.oice.scroolTop,
      scrollBottom: state => state.oice.scrollBottom
    }),
    loadingTip () {
      if (this.isLoading) {
        return '正在加载'
      } else if (this.isEnd) {
        return this.buildingList.length ? '没有更多了' : '暂无数据'
      } else {
        return ''
      }
    }
  }
}
</script>

<style lang="less">
  .filter .weui-btn {
    font-size:0.8em;
    border-right:0;
    border-radius:0;
    border-color:#ccc;
    background-color:#fbf9fe;
    white-space:nowrap;
    text-overflow:ellipsis;
  }

  .filter .vux-x-icon {
    fill:#aaa;
    position:relative;
    top:2px;
  }
</style>