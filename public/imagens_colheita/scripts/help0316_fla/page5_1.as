package help0316_fla
{
   import flash.display.*;
   import flash.events.*;
   
   [Embed(source="/_assets/assets.swf", symbol="symbol94")]
   public dynamic class page5_1 extends MovieClip
   {
      
      public var closeButton2:SimpleButton;
      
      public var preButton:SimpleButton;
      
      public function page5_1()
      {
         super();
         addFrameScript(0,frame1);
      }
      
      internal function frame1() : *
      {
         preButton.addEventListener(MouseEvent.CLICK,preButtonClick);
         closeButton2.addEventListener(MouseEvent.CLICK,closeButtonClick);
      }
      
      public function closeButtonClick(param1:MouseEvent) : void
      {
         dispatchEvent(new Event("closeEvent",true));
      }
      
      public function preButtonClick(param1:MouseEvent) : void
      {
         dispatchEvent(new Event("preButtonClick"));
      }
   }
}

