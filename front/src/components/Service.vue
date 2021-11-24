<template>
  <div class="right-float-btn">
    <a href="https://work.weixin.qq.com/kfid/kfc0beff0aaf2fd8d4a">
      <img src="/static/img/service.png" style="width:20px;height:20px;">
      <span style="position:relative;top:-5px;">客服</span>
    </a>
  </div>
</template>

<script>
export default {
  name: 'service'
}
</script>

<style lang="less">
  .right-float-btn {
    position:fixed;
    bottom:180px;
    right:0;
    padding:5px 5px 0 8px;
    height:25px;
    font-size: 0.8em;
    background-color: #fff;
    border-top:1px solid #c0c0c0;
    border-left:1px solid #c0c0c0;
    border-bottom:1px solid #c0c0c0;
    border-top-left-radius:8px;
    border-bottom-left-radius:8px;
    line-height: 1;
  }
</style>