// pages/building/edit/edit.js
import Dialog from '../../../miniprogram_npm/@vant/weapp/dialog/dialog';

const app = getApp()

Page({
  data: {
    id: 0,
    face: [{name: '东', type: 'default'},{name: '西', type: 'default'},{name: '南', type: 'default'},{name: '北', type: 'default'}],
    showFace: false,
    rent_sell: [{name: '出租'}, {name: '出售'}, {name: '出租，出售'}],
    showRentSell: false,
    decoration: [{name: '毛坯', type: 'default'}, {name: '吊顶', type: 'default'}, {name: '白墙', type: 'default'}, {name: '墙纸', type: 'default'}, {name: '水泥地', type: 'default'}, {name: '地砖', type: 'default'}, {name: '地毯', type: 'default'}, {name: '地板', type: 'default'}, {name: '精装修', type: 'default'}, {name: '客户遗留装修', type: 'default'}],
    showDecoration: false,
    status: [ 
      {name: '空置', value: 1}, 
      {name: '已出（隐藏）', value: 2}
    ],
    showStatus: false,
    showEndDate: false,
    numberEndDate: Date.now(),
    info: {
      __token__: '',
      building_id: 0,
      building_no: '',      // 楼栋
      floor: null,          // 楼层
      room: '',             // 房间号
      face: '',             // 朝向
      acreage: null,        // 面积
      rent_sell: '',        // 租售
      rent_price: null,     // 出租价格
      sell_price: null,     // 出售价格
      decoration: '',       // 装修状况
      status: 1,            // 状态(默认空置)
      end_date: '',         // 到期日
      rem: '',              // 备注
      user_id: 0,
      company_id: 0,        // 所属企业
      share: 1,             // 是否公开
      bool_share: true,
      linkman: '',          // 联系人
      mobile: ''            // 联系电话
    },
    is_room_empty: false,
    room_error: '',
    is_mobile_error: false,
    mobile_error: '',
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
    let url = 'unit/edit?id=' + that.data.id
    app.get(url, (res) => {
      if (res.success && res.data) {
        for (let item in that.data.info) {
          if (res.data[item] !== undefined && res.data[item] !== null) {
            that.setData({
              ['info.' + item]: res.data[item]
            })
          }
        }
        if (res.data.end_date) {
          that.setData({
            ['info.end_date']: app.formatTime(Date.parse(res.data.end_date.replace(/-/g, '/')), 'yyyy-MM-dd'),
            numberEndDate: Date.parse(res.data.end_date.replace(/-/g, '/'))
          })
        } else {
          that.setData({
            ['info.end_date']: ''
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
      wx.hideLoading()
    })
  },

  onBuildingInput: function(event) {
    this.setData({
      ['info.building_no']: event.detail
    })
  },

  onFloorInput: function(event) {
    this.setData({
      ['info.floor']: event.detail
    })
  },

  onRoomInput: function(event) {
    this.setData({
      ['info.room']: event.detail
    })
  },

  bindSelectFace: function() {
    this.setData({
      showFace: true
    })
  },

  onFaceClose: function() {
    let strFace = ''
    this.data.decoration.forEach(d => {
      if (d.type == 'success') {
        strFace+= d.name
      }
    })
    this.setData({
      showFace: false,
      ['info.face']: strFace
    })
  },

  onFaceTap: function(event) {
    let idx = event.target.dataset.data
    let arrFace = this.data.face
    if (arrFace[idx].type == 'default') {
      arrFace[idx].type = 'success'
    } else {
      arrFace[idx].type = 'default'
    }
    this.setData({
      face: arrFace
    })
  },

  onAcreageInput: function(event) {
    this.setData({
      ['info.acreage']: event.detail
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

  onRentPriceInput: function(event) {
    this.setData({
      ['info.rent_price']: event.detail
    })
  },

  onSellPriceInput: function(event) {
    this.setData({
      ['info.sell_price']: event.detail
    })
  },

  bindSelectDecoration: function() {
    this.setData({
      showDecoration: true
    })
  },

  onDecorationClose: function() {
    let strDecoration = ''
    this.data.decoration.forEach(d => {
      if (d.type == 'success') {
        if (strDecoration.length) {
          strDecoration += ','
        }
        strDecoration+= d.name
      }
    })
    this.setData({
      showDecoration: false,
      ['info.decoration']: strDecoration
    })
  },

  onDecorationTap: function(event) {
    let idx = event.target.dataset.data
    let arrDecoration = this.data.decoration
    if (arrDecoration[idx].type == 'default') {
      arrDecoration[idx].type = 'success'
    } else {
      arrDecoration[idx].type = 'default'
    }
    this.setData({
      decoration: arrDecoration
    })
  },

  bindSelectStatus: function() {
    this.setData({
      showStatus: true
    })
  },

  onStatusClose: function() {
    this.setData({
      showStatus: false
    })
  },

  onStatusSelect: function(event) {
    this.setData({
      ['info.status']: event.detail.value,
      showStatus: false
    })
  },

  bindSelectEndDate: function() {
    this.setData({
      showEndDate: true
    })
  },

  onEndDateConfirm: function(event) {
    this.setData({
      ['info.end_date']: app.formatTime(event.detail, 'yyyy-MM-dd'),
      showEndDate: false
    })
  },

  onEndDateClose: function() {
    this.setData({
      showEndDate: false
    })
  },

  onRemInput: function(event) {
    this.setData({
      ['info.rem']: event.detail
    })
  },

  onLinkmanInput: function(event) {
    this.setData({
      ['info.linkman']: event.detail
    })
  },

  onMobileInput: function(event) {
    this.setData({
      ['info.mobile']: event.detail
    })
    if (event.detail) {
      if (app.isMobile(event.detail)) {
        this.setData({
          mobile_error: ''
        })
      }
    }
  },

  onShareChange: function(event) {
    this.setData({
      ['info.share']: event.detail ? 1 : 0
    })
  },

  bindSave: function() {
    let that = this
    let error = 0
    if (!that.data.info.room) {
      error++
      that.setData({
        is_room_empty: true,
        room_error: '请输入房间号'
      })
    } else {
      that.setData({
        is_room_empty: false,
        room_error: ''
      })
    }

    if (that.data.info.mobile && !app.isMobile(that.data.info.mobile)) {
      error++
      that.setData({
        is_mobile_error: true,
        mobile_error: '请填写有效的手机号码'
      })
    } else {
      that.setData({
        is_mobile_error: false,
        mobile_error: ''
      })
    }

    if (error > 0) {
      return
    }

    wx.showLoading({
      title: '保存中',
    })

    app.post('unit/edit?id=' + that.data.id, that.data.info, (res) => {
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
})