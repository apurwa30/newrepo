
<style type="text/css">
    div{
        
    }
</style>

<?php

defined('MOODLE_INTERNAL') || die();

abstract class qtype_multiple_choice_renderer_base extends qtype_with_combined_feedback_renderer {

   
    protected abstract function after_choices(question_attempt $qa, question_display_options $options);

    protected abstract function get_input_type();

    protected abstract function get_input_name(question_attempt $qa, $value);

    protected abstract function get_input_value($value);

    protected abstract function get_input_id(question_attempt $qa, $value);

    
    protected abstract function is_right(question_answer $ans);

    protected abstract function prompt();

    public function formulation_and_controls(question_attempt $qa,
            question_display_options $options) {

        $question = $qa->get_question();
        $response = $question->get_response($qa);

        $inputname = $qa->get_qt_field_name('answer');
        $inputattributes = array(
            'type' => $this->get_input_type(),
            'name' => $inputname,
        );

        if ($options->readonly) {
            $inputattributes['disabled'] = 'disabled';
        }

        $radiobuttons = array();
        $feedbackimg = array();
        $feedback = array();
        $classes = array();
        foreach ($question->get_order($qa) as $value => $ansid) {
            $ans = $question->answers[$ansid];
            $inputattributes['name'] = $this->get_input_name($qa, $value);
            $inputattributes['value'] = $this->get_input_value($value);
            $inputattributes['id'] = $this->get_input_id($qa, $value);
            $isselected = $question->is_choice_selected($response, $value);
            if ($isselected) {
                $inputattributes['checked'] = 'checked';
            } else {
                unset($inputattributes['checked']);
            }
            $hidden = '';
            if (!$options->readonly && $this->get_input_type() == 'checkbox') {
                $hidden = html_writer::empty_tag('input', array(
                    'type' => 'hidden',
                    'name' => $inputattributes['name'],
                    'value' => 0,
                ));
            }
            $radiobuttons[] = $hidden . html_writer::empty_tag('input', $inputattributes) .
                    html_writer::tag('label',
                        html_writer::span($this->number_in_style($value, $question->answernumbering), 'answernumber') .
                        $question->make_html_inline($question->format_text(
                                $ans->answer, $ans->answerformat,
                                $qa, 'question', 'answer', $ansid)),
                        array('for' => $inputattributes['id'], 'class' => 'ml-1'));

            // Param $options->suppresschoicefeedback is a hack specific to the
            // oumultiresponse question type. It would be good to refactor to
            // avoid refering to it here.
            if ($options->feedback && empty($options->suppresschoicefeedback) &&
                    $isselected && trim($ans->feedback)) {
                $feedback[] = html_writer::tag('div',
                        $question->make_html_inline($question->format_text(
                                $ans->feedback, $ans->feedbackformat,
                                $qa, 'question', 'answerfeedback', $ansid)),
                        array('class' => 'specificfeedback'));
            } else {
                $feedback[] = '';
            }
            $class = 'r' . ($value % 2);
            if ($options->correctness && $isselected) {
                $feedbackimg[] = $this->feedback_image($this->is_right($ans));
                $class .= ' ' . $this->feedback_class($this->is_right($ans));
            } else {
                $feedbackimg[] = '';
            }
            $classes[] = $class;
        }

        $result = '';
        $result .= html_writer::tag('div', $question->format_questiontext($qa),
                array('class' => 'qtext'));

        $result .= html_writer::start_tag('div', array('class' => 'ablock'));
        $result .= html_writer::tag('div', $this->prompt(), array('class' => 'prompt'));

        $result .= html_writer::start_tag('div', array('class' => 'answer'));
        foreach ($radiobuttons as $key => $radio) {
            $result .= $radio . ' ' . $feedbackimg[$key] . $feedback[$key].
                    "\n";
        }
        $result .= html_writer::end_tag('div'); // Answer.

        $result .= $this->after_choices($qa, $options);

        $result .= html_writer::end_tag('div'); // Ablock.

        if ($qa->get_state() == question_state::$invalid) {
            $result .= html_writer::nonempty_tag('div',
                    $question->get_validation_error($qa->get_last_qt_data()),
                    array('class' => 'validationerror'));
        }

        return $result;
    }

    protected function number_html($qnum) {
        return $qnum . '. ';
    }

    protected function number_in_style($num, $style) {
        switch($style) {
            case 'abc':
                $number = chr(ord('a') + $num);
                break;
            case 'ABCD':
                $number = chr(ord('A') + $num);
                break;
            case '123':
                $number = $num + 1;
                break;
            case 'iii':
                $number = question_utils::int_to_roman($num + 1);
                break;
            case 'IIII':
                $number = strtoupper(question_utils::int_to_roman($num + 1));
                break;
            case 'none':
                return '';
            default:
                return 'ERR';
        }
        return $this->number_html($number);
    }

    public function specific_feedback(question_attempt $qa) {
        return $this->combined_feedback($qa);
    }

    
    protected function correct_choices(array $right) {
        // Return appropriate string for single/multiple correct answer(s).
        if (count($right) == 1) {
                return get_string('correctansweris', 'qtype_multiple_choice',
                        implode(', ', $right));
        } else if (count($right) > 1) {
                return get_string('correctanswersare', 'qtype_multiple_choice',
                        implode(', ', $right));
        } else {
                return "";
        }
    }
}


/**
 * Subclass for generating the bits of output specific to multiple choice
 * single questions.
 *
 * @copyright  2009 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_multiple_choice_single_renderer extends qtype_multiple_choice_renderer_base {
    protected function get_input_type() {
        return 'radio';
    }

    protected function get_input_name(question_attempt $qa, $value) {
        return $qa->get_qt_field_name('answer');
    }

    protected function get_input_value($value) {
        return $value;
    }

    protected function get_input_id(question_attempt $qa, $value) {
        return $qa->get_qt_field_name('answer' . $value);
    }

    protected function is_right(question_answer $ans) {
        return $ans->fraction;
    }

    protected function prompt() {
        return get_string('selectone', 'qtype_multiple_choice');
    }

    public function correct_response(question_attempt $qa) {
        $question = $qa->get_question();

        // Put all correct answers (100% grade) into $right.
        $right = array();
        foreach ($question->answers as $ansid => $ans) {
            if (question_state::graded_state_for_fraction($ans->fraction) ==
                    question_state::$gradedright) {
                $right[] = $question->make_html_inline($question->format_text($ans->answer, $ans->answerformat,
                        $qa, 'question', 'answer', $ansid));
            }
        }
        return $this->correct_choices($right);
    }

    public function after_choices(question_attempt $qa, question_display_options $options) {
        // Only load the clear choice feature if it's not read only.
        if ($options->readonly) {
            return '';
        }

        $question = $qa->get_question();
        $response = $question->get_response($qa);
        $hascheckedchoice = false;
        foreach ($question->get_order($qa) as $value => $ansid) {
            if ($question->is_choice_selected($response, $value)) {
                $hascheckedchoice = true;
                break;
            }
        }

        $clearchoiceid = $this->get_input_id($qa, -1);
        $clearchoicefieldname = $qa->get_qt_field_name('clearchoice');
        $clearchoiceradioattrs = [
            'type' => $this->get_input_type(),
            'name' => $qa->get_qt_field_name('answer'),
            'id' => $clearchoiceid,
            'value' => -1,
            'class' => 'sr-only'
        ];

        $cssclass = 'qtype_multiple_choice_clearchoice';
        // When no choice selected during rendering, then hide the clear choice option.
        if (!$hascheckedchoice && $response == -1) {
            $cssclass .= ' sr-only';
            $clearchoiceradioattrs['checked'] = 'checked';
        }
        // Adds an hidden radio that will be checked to give the impression the choice has been cleared.
        $clearchoiceradio = html_writer::empty_tag('input', $clearchoiceradioattrs);
        $clearchoiceradio .= html_writer::link('', get_string('clearchoice', 'qtype_multiple_choice'),
            ['for' => $clearchoiceid, 'role' => 'button']);

        // Now wrap the radio and label inside a div.
        $result = html_writer::tag('div', $clearchoiceradio, ['id' => $clearchoicefieldname, 'class' => $cssclass]);

        // Load required clearchoice AMD module.
        $this->page->requires->js_call_amd('qtype_multiple_choice/clearchoice', 'init',
            [$qa->get_outer_question_div_unique_id(), $clearchoicefieldname]);

        return $result;
    }

}

/**
 * Subclass for generating the bits of output specific to multiple choice
 * multi=select questions.
 *
 * @copyright  2009 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_multiple_choice_multi_renderer extends qtype_multiple_choice_renderer_base {
    protected function after_choices(question_attempt $qa, question_display_options $options) {
        return '';
    }

    protected function get_input_type() {
        return 'checkbox';
    }

    protected function get_input_name(question_attempt $qa, $value) {
        return $qa->get_qt_field_name('choice' . $value);
    }

    protected function get_input_value($value) {
        return 1;
    }

    protected function get_input_id(question_attempt $qa, $value) {
        return $this->get_input_name($qa, $value);
    }

    protected function is_right(question_answer $ans) {
        if ($ans->fraction > 0) {
            return 1;
        } else {
            return 0;
        }
    }

    protected function prompt() {
        return get_string('selectmulti', 'qtype_multiple_choice');
    }

    public function correct_response(question_attempt $qa) {
        $question = $qa->get_question();

        $right = array();
        foreach ($question->answers as $ansid => $ans) {
            if ($ans->fraction > 0) {
                $right[] = $question->make_html_inline($question->format_text($ans->answer, $ans->answerformat,
                        $qa, 'question', 'answer', $ansid));
            }
        }
        return $this->correct_choices($right);
    }

    protected function num_parts_correct(question_attempt $qa) {
        if ($qa->get_question()->get_num_selected_choices($qa->get_last_qt_data()) >
                $qa->get_question()->get_num_correct_choices()) {
            return get_string('toomanyselected', 'qtype_multiple_choice');
        }

        return parent::num_parts_correct($qa);
    }
}
