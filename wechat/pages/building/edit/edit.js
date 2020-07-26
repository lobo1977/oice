// pages/building/edit/edit.js
import Dialog from '../../../miniprogram_npm/@vant/weapp/dialog/dialog';

const app = getApp()

Page({
  data: {
    id: 0,
    area: [],
    showArea: false,
    type:[],
    showType: false,
    level: [{name: '甲级', value: '甲'},{name: '乙级', value: '乙'},{name: '丙级', value: '丙'},{name: '未定级', value: ''}],
    showLevel: false,
    rent_sell: [{name: '出租'}, {name: '出售'}, {name: '出租，出售'}],
    showRentSell: false,
    district: [],
    showDistrict: false,
    showCompletionDate: false,
    numberCompletionDate: Date.now(),
    status: [ 
      {name: '潜在', value: 0}, 
      {name: '跟进', value: 1}, 
      {name: '看房', value: 2}, 
      {name: '洽谈', value: 3}, 
      {name: '成交', value: 4}, 
      {name: '失败', value: 5}, 
      {name: '名录', value: 6}
    ],
    showStatus: false,
    info: {
      __token__: '',
      building_name: '',    // 名称
      type: '',             // 类别
      level: '',            // 等级
      area: '',             // 城区
      district: '',         // 商圈
      address: '',          // 地址
      longitude: 0,          // 经度
      latitude: 0,           // 纬度
      completion_date: '', // 竣工日期
      rent_sell: '',        // 租售
      price: '',            // 价格
      acreage: null,        // 建筑面积
      // usage_area: null,  // 使用面积
      // floor: '',            // 楼层
      // floor_area: null,     // 标准层面积
      floor_height: null,   // 层高
      bearing: null,        // 楼板承重
      developer: '',        // 开发商
      manager: '',          // 物业管理
      fee: '',              // 物业费
      // electricity_fee: '',  // 电费
      // car_seat: '',         // 停车位
      rem: '',              // 项目说明
      facility: '',         // 配套设施
      equipment: '',        // 楼宇设备
      traffic: '',          // 交通状况
      environment: '',      // 周边环境
      company_id: '',       // 所属企业
      user_id: 0,
      share: 1,             // 是否公开
      send_sms: 0           // 是否发送委托确认短信
    },
    engInfo: {
      __token__: '',
      name: '',
      location: '',
      situation: '',
      developer: '',
      manager: '',
      network: '',
      elevator: '',
      hvac: '',
      amenities: '',
      tenants: ''
    },
    is_name_empty: false,
    name_error: ''
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    let that = this
    if (options.id) {
      that.setData({
        id: options.id
      })
    }
    if (app.globalData.appUserInfo) {
      that.getData()
    } else {
      app.userLoginCallback = () => {
        that.getData()
      }
    }
  },

  /**
   * 生命周期函数--监听页面初次渲染完成
   */
  onReady: function () {

  },

  /**
   * 生命周期函数--监听页面显示
   */
  onShow: function () {
  },

  /**
   * 生命周期函数--监听页面隐藏
   */
  onHide: function () {

  },

  /**
   * 生命周期函数--监听页面卸载
   */
  onUnload: function () {

  },

  /**
   * 页面相关事件处理函数--监听用户下拉动作
   */
  onPullDownRefresh: function () {

  },

  /**
   * 页面上拉触底事件的处理函数
   */
  onReachBottom: function () {

  },

  /**
   * 用户点击右上角分享
   */
  onShareAppMessage: function () {

  },

  // 获取数据
  getData: function() {
    let that = this
    wx.showLoading({
      title: '加载中',
    })
    let url = 'building/edit?id=' + that.data.id
    app.get(url, (res) => {
      if (res.success && res.data) {
        for (let item in that.data.info) {
          if (res.data[item] !== undefined && res.data[item] !== null) {
            that.setData({
              ['info.' + item]: res.data[item]
            })
          }
        }
        if (res.data.user_id === 0 || res.data.user_id === app.globalData.appUserInfo.id) {
          that.setData({
            ['info.send_sms']: 1
          })
        }
        if (res.data.completion_date) {
          that.setData({
            ['info.completion_date']: app.formatTime(Date.parse(res.data.completion_date.replace(/-/g, '/')), 'yyyy-MM-dd'),
            numberCompletionDate: Date.parse(res.data.completion_date.replace(/-/g, '/'))
          })
        } else {
          that.setData({
            ['info.completion_date']: ''
          })
        }
      } else {
        Dialog.alert({
          title: '发生错误',
          message: res.message ? res.message : '系统异常'
        }).then(() => {
          wx.navigateBack()
        })
      }
    }, () => {
      let arrArea = []
      let arrDistrict = []
      app.globalData.area.forEach(element => {
        if (element.id != 'all') {
          arrArea.push(element.text)
          if (element.children && element.children.length) {
            let dataDistrict = that.data.info.district ? that.data.info.district : ''
            element.children.forEach(d => {
              if (d.id != '') {
                arrDistrict.push( { text: d.text, type: dataDistrict.indexOf(d.text) >= 0 ? 'success' : 'default' })
              }
            })
          }
        }
      })
      let arrType = []
      app.globalData.buildingType.forEach(element => {
        if (element.text != '类别') {
          arrType.push(element.text)
        }
      })
      that.setData({
        area: arrArea,
        type: arrType,
        district: arrDistrict
      })
      wx.hideLoading()
    })
  },

  onNameInput: function(event) {
    this.setData({
      ['info.building_name']: event.detail
    })
  },

  // onTelInput: function(event) {
  //   this.setData({
  //     ['info.tel']: event.detail
  //   })
  // },

  // onLinkmanInput: function(event) {
  //   this.setData({
  //     ['info.linkman']: event.detail
  //   })
  // },

  onAddressInput: function(event) {
    this.setData({
      ['info.address']: event.detail
    })
  },

  bindSelectArea: function() {
    this.setData({
      showArea: true
    })
  },

  onAreaPickerClose: function() {
    this.setData({
      showArea: false
    })
  },

  onAreaSelected: function(event) {
    this.setData({
      ['info.area']: event.detail.value,
      showArea: false
    })
  },

  bindSelectType: function() {
    this.setData({
      showType: true
    })
  },

  onTypePickerClose: function() {
    this.setData({
      showType: false
    })
  },

  onTypeSelected: function(event) {
    this.setData({
      ['info.type']: event.detail.value,
      showType: false
    })
  },

  bindSelectLevel: function() {
    this.setData({
      showLevel: true
    })
  },

  onLevelClose: function() {
    this.setData({
      showLevel: false
    })
  },

  onLevelSelect: function(event) {
    this.setData({
      ['info.level']: event.detail.value,
      showLevel: false
    })
  },

  bindSelectRentSell: function() {
    this.setData({
      showRentSell: true
    })
  },

  onRentSellClose: function() {
    this.setData({
      showRentSell: false
    })
  },

  onRentSellSelect: function(event) {
    this.setData({
      ['info.rent_sell']: event.detail.name,
      showRentSell: false
    })
  },

  bindSelectDistrict: function() {
    this.setData({
      showDistrict: true
    })
  },

  onDistrictClose: function() {
    let strDistrict = ''
    this.data.district.forEach(d => {
      if (d.type == 'success') {
        if (strDistrict.length) {
          strDistrict += ','
        }
        strDistrict+= d.text
      }
    })
    this.setData({
      showDistrict: false,
      ['info.district']: strDistrict
    })
  },

  onDistrictTap: function(event) {
    let idx = event.target.dataset.data
    let arrDistrict = this.data.district
    if (arrDistrict[idx].type == 'default') {
      arrDistrict[idx].type = 'success'
    } else {
      arrDistrict[idx].type = 'default'
    }
    this.setData({
      district: arrDistrict
    })
  },

  onAcreageInput: function(event) {
    this.setData({
      ['info.acreage']: event.detail
    })
  },

  onPriceInput: function(event) {
    this.setData({
      ['info.price']: event.detail
    })
  },

  onFloorHeightInput: function(event) {
    this.setData({
      ['info.floor_height']: event.detail
    })
  },

  onBearingInput: function(event) {
    this.setData({
      ['info.bearing']: event.detail
    })
  },

  onDeveloperInput: function(event) {
    this.setData({
      ['info.developer']: event.detail
    })
  },

  onManagerInput: function(event) {
    this.setData({
      ['info.manager']: event.detail
    })
  },

  onFeeInput: function(event) {
    this.setData({
      ['info.fee']: event.detail
    })
  },

  bindSelectCompletionDate: function() {
    this.setData({
      showCompletionDate: true
    })
  },

  onCompletionDateConfirm: function(event) {
    this.setData({
      ['info.completion_date']: app.formatTime(event.detail, 'yyyy-MM-dd'),
      showCompletionDate: false
    })
  },

  onCompletionDateClose: function() {
    this.setData({
      showCompletionDate: false
    })
  },

  onRemInput: function(event) {
    this.setData({
      ['info.rem']: event.detail
    })
  },

  onEquipmentInput: function(event) {
    this.setData({
      ['info.equipment']: event.detail
    })
  },

  onTrafficInput: function(event) {
    this.setData({
      ['info.traffic']: event.detail
    })
  },

  onFacilityInput: function(event) {
    this.setData({
      ['info.facility']: event.detail
    })
  },

  onEnvironmentInput: function(event) {
    this.setData({
      ['info.environment']: event.detail
    })
  },

  onShareChange: function(event) {
    this.setData({
      ['info.share']: event.detail ? 1 : 0
    })
  },

  onSmsChange: function(event) {
    this.setData({
      ['info.send_sms']: event.detail ? 1 : 0
    })
  },

  bindSave: function() {
    let that = this
    if (!that.data.info.building_name) {
      that.setData({
        is_name_empty: true,
        name_error: '请输入项目名称'
      })
    } else {
      that.setData({
        is_name_empty: false,
        name_error: ''
      })

      wx.showLoading({
        title: '保存中',
      })

      app.post('building/edit?id=' + that.data.id, that.data.info, (res) => {
        if (res.success) {
          app.globalData.refreshBuilding = true
          if (that.data.id == 0) {
            let id = res.data
            wx.redirectTo({
              url: '../view/view?id=' + id
            })
          } else {
            wx.navigateBack()
          }
        } else {
          if (res.data && res.data.token) {
            that.data.info.__token__ = res.data.token
          }
          if (res.message) {
            wx.showToast({
              icon: 'none',
              title: res.message,
              duration: 2000
            })
          } else {
            wx.showToast({
              icon: 'none',
              title: '操作失败，系统异常',
              duration: 2000
            })
          }
        }
      }, () => {
        wx.hideLoading()
      })
    }
  }
})