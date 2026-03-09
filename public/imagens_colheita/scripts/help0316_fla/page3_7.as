package help0316_fla
{
   import flash.display.*;
   import flash.events.*;
   
   [Embed(source="/_assets/assets.swf", symbol="symbol64")]
   public dynamic class page3_7 extends MovieClip
   {
      
      public var nextButton:SimpleButton;
      
      public var preButton:SimpleButton;
      
      public function page3_7()
      {
         super();
         addFrameScript(0,frame1);
      }
      
      internal function frame1() : *
      {
         nextButton.addEventListener(MouseEvent.CLICK,nextButtonClick);
         preButton.addEventListener(MouseEvent.CLICK,preButtonClick);
      }
      
      public function nextButtonClick(param1:MouseEvent) : void
      {
         dispatchEvent(new Event("nextButtonClick"));
      }
      
      public function preButtonClick(param1:MouseEvent) : void
      {
         dispatchEvent(new Event("preButtonClick"));
      }
   }
}

