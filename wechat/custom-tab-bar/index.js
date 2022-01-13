const app = getApp()

Component({
  properties: {
  },

  /**
   * 页面的初始数据
   */
  data: {
    selected: 1,
    color: __wxConfig.tabBar.color,
    selectedColor: __wxConfig.tabBar.selectedColor,
    listTab: [
      {
        "text": "首页",
        "pagePath": "/pages/index/index",
        "iconPath": "/icon/home_a.png",
        "selectedIconPath": "/icon/home_b.png"
      },
      {
        "text": "项目",
        "pagePath": "/pages/building/index/index",
        "iconPath": "/icon/building_a.png",
        "selectedIconPath": "/icon/building_b.png"
      },
      {
        "text": "发布",
        "pagePath": "/pages/building/edit/edit",
        "iconPath": "/icon/add.png",
        "isSpecial": true
      },
      {
        "text": "客户",
        "pagePath": "/pages/customer/index/index",
        "iconPath": "/icon/contact_a.png",
        "selectedIconPath": "/icon/contact_b.png"
      },
      {
        "text": "我的",
        "pagePath": "/pages/my/index/index",
        "iconPath": "/icon/my_a.png",
        "selectedIconPath": "/icon/my_b.png"
      }
    ]
  },

  methods: {
    switchTab(e) {
      const data = e.currentTarget.dataset
      let url = data.path
      if (data.special == 1) {
        app.checkUser(url)
      } else {
        wx.switchTab({url})
      }
    }
  }
})