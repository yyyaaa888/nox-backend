<?php

namespace App\Admin\Forms;

class Form extends \Dcat\Admin\Form
{
    /**
     * @return void
     */
    public function build()
    {
        parent::build();
    }

    /**
     * Prepare input data for insert or update.
     *
     * @param  array  $data
     * @return Response|null
     */
    public function prepare($data = [])
    {
        $this->inputs = $this->removeIgnoredFields($data);

        if ($response = $this->callSaving()) {
            return $response;
        }

        $this->updates = $this->inputs;
    }

    /**
     * 触发表单保存事件.
     *
     * @return mixed
     */
    public function callSaving()
    {
        return $this->fire(\Dcat\Admin\Form\Events\Saving::class);
    }
}
