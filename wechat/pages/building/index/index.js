//index.js
import { areaList } from '@vant/area-data';

//获取应用实例
const app = getApp()

Page({
  data: {
    me: null,
    city: '',
    areaList,
    pageIndex: 1,
    pageSize: 10,
    isLoading: false,
    isPullDown: false,
    isEnd: false,
    keyword: '',
    type: '',
    area: '',
    district: '',
    rent_sell: '',
    acreage: '',
    mainDropIndex: 0,
    filterDropTitle: "区域",
    filterType: [],
    filterArea: [],
    filterRentSell: [
      {
        text: "租售",
        value: "出租,出售"
      },
      {
        text: "出租",
        value: "出租"
      },
      {
        text: "出售",
        value: "出售"
      }
    ],
    filterAcreage: [
      {
        text: "面积",
        value: "0,0"
      }, {
        text: "200平米以下",
        value: "0,200"
      },{
        text: "200-300平米",
        value: "200,300"
      }, {
        text: "300-500平米",
        value: "300,500"
      }, {
        text: "500-1000平米",
        value: "500,1000"
      }, {
        text: "1000平米以上",
        value: "1000,0"
      }
    ],
    list: []
  },
  onLoad: function(options) {
    wx.showShareMenu({
      withShareTicket:true,
      menus:['shareAppMessage','shareTimeline']  
    })

    this.setFilterArea()

    this.setData({
      filterType: app.globalData.buildingType,
    })

    if (app.globalData.appUserInfo) {
      this.setData({
        me: app.globalData.appUserInfo
      })
      this.getList()
    } else {
      app.userLoginCallback = () => {
        this.setData({
          me: app.globalData.appUserInfo
        })
        this.getList()
      }
    }
  },
  onShow: function() {
    if (app.globalData.changeCity) {
      app.globalData.refreshBuilding = true
      app.globalData.changeCity = false
      this.setFilterArea()
    }
    if (app.globalData.refreshBuilding) {
      app.globalData.refreshBuilding = false
      this.getList()
    }
  },
  onReady: function() {
    // 页面首次渲染完毕时执行
  },
  onHide: function() {
    // 页面从前台变为后台时执行
  },
  onUnload: function() {
    // 页面销毁时执行
  },
  onPullDownRefresh: function() {
    // 触发下拉刷新时执行
    if (this.data.isLoading == false) {
      this.setData({
        isPullDown: true
      })
      this.data.pageIndex = 1
      this.getList()
    }
    wx.stopPullDownRefresh()
  },
  onReachBottom: function() {
    if (this.data.isEnd == false) {
      this.data.pageIndex++
      this.getList()
    }
  },
  onShareAppMessage: function () {
    // 页面被用户分享时执行
  },
  
  onKewordChange(e) {
    this.data.keyword = e.detail
  },

  // 搜索项目
  onSearch: function(event) {
    this.data.pageIndex = 1
    this.getList()
  },

  onCancel: function() {
    this.setData({
      keyword: ''
    })
    this.data.pageIndex = 1
    this.getList()
  },

  onClickDropNav({ detail = {} }) {
    this.setData({
      mainDropIndex: detail.index || 0,
      district: "",
      area: this.data.filterArea[detail.index || 0].id,
      filterDropTitle: this.data.filterArea[detail.index || 0].text,
      pageIndex: 1
    })
    if (this.data.filterArea[this.data.mainDropIndex].children.length == 0) {
      this.selectComponent('#dropArea').toggle();
      this.getList()
    }
  },

  onClickDropItem({ detail = {} }) {
    this.setData({
      district: detail.id,
      filterDropTitle: detail.id || this.data.filterDropTitle,
      pageIndex: 1
    })

    this.selectComponent('#dropArea').toggle();
    this.getList()
  },

  filterTypeChange(event) {
    this.data.type = event.detail
    this.data.pageIndex = 1
    this.getList()
  },

  filterRentSellChange(event) {
    this.data.rent_sell = event.detail
    this.data.pageIndex = 1
    this.getList()
  },

  filterAcreageChange(event) {
    this.data.acreage = event.detail
    this.data.pageIndex = 1
    this.getList()
  },

  bindAdd: function() {
    app.checkUser('/pages/building/edit/edit')
  },

  setFilterArea: function() {
    let filterArea = app.globalData.area
    if (app.globalData.currentCity != '北京市') {
      filterArea = []
      filterArea.push({
        text: "所有区域",
        id: "all",
        children: [
        ]
      })

      let areaCode = ''
      for(let code in areaList.city_list) {
        if (areaList.city_list[code] == app.globalData.currentCity) {
          areaCode = code.substring(0, 4)
          break
        }
      }

      if (areaCode) {
        for(let code in areaList.county_list) {
          if (code.indexOf(areaCode) == 0) {
            filterArea.push({
              text: areaList.county_list[code],
              id: areaList.county_list[code],
              children: [
                {
                  text: "全区",
                  id: ""
                }
              ]
            })
          }
        }
      }
    }

    this.setData({
      city: app.globalData.currentCity,
      filterArea: filterArea
    })
  },

  switchCity: function() {
    wx.navigateTo({
      url: '../../city/city'
    })
  },
    
  // 获取列表
  getList: function() {
    let that = this

    if (that.data.isLoading) return

    that.setData({
      isLoading: true
    })

    if (that.data.pageIndex <= 1) {
      that.setData({
        list: []
      })
    }

    app.post('building/index', { 
      page: that.data.pageIndex,
      keyword: that.data.keyword,
      city: that.data.city,
      type: that.data.type,
      area: that.data.area,
      district: that.data.district,
      rent_sell: that.data.rent_sell,
      acreage: that.data.rent_sells
    }, (res) => {
      if (!res.data || res.data.length < that.data.pageSize) {
        that.setData({
          isEnd: true
        })
      } else {
        that.setData({
          isEnd: false
        })
      }
      if (res.data) {
        let list = that.data.list.concat(res.data)
        that.setData({
          list: list
        })
      }
    }, () => {
      that.setData({
        isPullDown: false,
        isLoading: false
      })
    })
  }
})